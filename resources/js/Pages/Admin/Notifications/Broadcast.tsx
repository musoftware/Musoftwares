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
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { formatDate } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import { Link } from '@inertiajs/react';
import { DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem } from '@/Components/ui/dropdown-menu';
import { MoreHorizontal, Eye } from 'lucide-react';

export default function Broadcast({ campaigns = [] }: { campaigns?: any[] }) {
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

            <div className="max-w-4xl mx-auto py-8 space-y-6">
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

                <Card>
                    <CardHeader>
                        <CardTitle>{__('admin.past_campaigns')}</CardTitle>
                        <CardDescription>
                            {__('admin.past_campaigns_description')}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {campaigns.length === 0 ? (
                            <div className="text-center py-8 text-slate-500">
                                {__('admin.no_campaigns_yet')}
                            </div>
                        ) : (
                            <div className="rounded-md border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>{__('general.title')}</TableHead>
                                            <TableHead>{__('admin.date')}</TableHead>
                                            <TableHead className="text-center">{__('freelance.views')}</TableHead>
                                            <TableHead>{__('general.status')}</TableHead>
                                            <TableHead className="text-right">{__('general.actions')}</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {campaigns.map((campaign) => (
                                            <TableRow key={campaign.id}>
                                                <TableCell className="font-medium">{campaign.title}</TableCell>
                                                <TableCell className="text-slate-500 text-sm">{formatDate(campaign.created_at)}</TableCell>
                                                <TableCell className="text-center">
                                                    {campaign.target_url ? (
                                                        <Badge variant="secondary" className="px-3 py-1 font-bold">
                                                            {campaign.clicks_count}
                                                        </Badge>
                                                    ) : (
                                                        <span className="text-slate-400 text-xs">-</span>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge className={campaign.status === 'completed' ? 'bg-green-500 hover:bg-green-600' : ''} variant={campaign.status === 'completed' ? 'default' : 'secondary'}>
                                                        {__(campaign.status)}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button variant="ghost" className="h-8 w-8 p-0">
                                                                <span className="sr-only">Open menu</span>
                                                                <MoreHorizontal className="h-4 w-4" />
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end">
                                                            <DropdownMenuItem asChild>
                                                                <Link href={route('admin.notifications.broadcast.show', campaign.id)} className="flex items-center cursor-pointer">
                                                                    <Eye className="w-4 h-4 mr-2" />
                                                                    {__('general.view')}
                                                                </Link>
                                                            </DropdownMenuItem>
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AdminSidebarLayout>
    );
}

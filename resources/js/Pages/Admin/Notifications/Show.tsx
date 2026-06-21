import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { ArrowLeft, BarChart3, Eye, MousePointerClick, Calendar, Send, Link as LinkIcon, Users } from 'lucide-react';
import { formatDate } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';

export default function Show({ campaign }: { campaign: any }) {
    // Calculate CTR
    const ctr = campaign.views_count > 0 
        ? ((campaign.clicks_count / campaign.views_count) * 100).toFixed(2) 
        : '0.00';

    const views = campaign.views?.filter((v: any) => v.type === 'view') || [];
    const clicks = campaign.views?.filter((v: any) => v.type === 'click') || [];

    return (
        <AdminSidebarLayout>
            <Head title={campaign.title} />

            <div className="max-w-5xl mx-auto py-8 space-y-8">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href={route('admin.notifications.broadcast')}>
                            <Button variant="outline" size="icon" className="h-8 w-8">
                                <ArrowLeft className="w-4 h-4" />
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
                                {campaign.title}
                                {campaign.audience_type === 'personal' && (
                                    <Badge variant="outline" className="ms-2 bg-indigo-50 text-indigo-700 border-indigo-200">
                                        Personal
                                    </Badge>
                                )}
                            </h1>
                            <p className="text-sm text-slate-500 flex items-center gap-1 mt-1">
                                <Calendar className="w-3.5 h-3.5" />
                                {formatDate(campaign.created_at)}
                            </p>
                        </div>
                    </div>
                    <div>
                        <Badge className={campaign.status === 'completed' ? 'bg-green-500 hover:bg-green-600' : ''} variant={campaign.status === 'completed' ? 'default' : 'secondary'}>
                            {__(campaign.status)}
                        </Badge>
                    </div>
                </div>

                {/* Stats Grid */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-slate-500">
                                {__('freelance.views')}
                            </CardTitle>
                            <Eye className="w-4 h-4 text-blue-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold text-slate-900">
                                {campaign.views_count?.toLocaleString() || 0}
                            </div>
                            <p className="text-xs text-slate-500 mt-1">
                                Total unique device fetches
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-slate-500">
                                {__('admin.clicks')}
                            </CardTitle>
                            <MousePointerClick className="w-4 h-4 text-purple-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold text-slate-900">
                                {campaign.clicks_count?.toLocaleString() || 0}
                            </div>
                            <p className="text-xs text-slate-500 mt-1">
                                Users who clicked the notification
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-slate-500">
                                Click-Through Rate
                            </CardTitle>
                            <BarChart3 className="w-4 h-4 text-emerald-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold text-slate-900">
                                {ctr}%
                            </div>
                            <p className="text-xs text-slate-500 mt-1">
                                Clicks per view ratio
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {campaign.audience_type === 'personal' && (
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-lg">
                                    <Eye className="w-5 h-5 text-blue-500" />
                                    {__('admin.who_viewed')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {views.length === 0 ? (
                                    <div className="text-center py-4 text-slate-500 text-sm">
                                        No views tracked yet.
                                    </div>
                                ) : (
                                    <div className="max-h-64 overflow-y-auto pe-2 border rounded-md">
                                        <Table>
                                            <TableHeader className="bg-slate-50 sticky top-0">
                                                <TableRow>
                                                    <TableHead>User</TableHead>
                                                    <TableHead className="text-end">Time</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {views.map((view: any) => (
                                                    <TableRow key={view.id}>
                                                        <TableCell className="font-medium">{view.user?.name || 'Unknown'}</TableCell>
                                                        <TableCell className="text-end text-xs text-slate-500">{formatDate(view.created_at)}</TableCell>
                                                    </TableRow>
                                                ))}
                                            </TableBody>
                                        </Table>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-lg">
                                    <MousePointerClick className="w-5 h-5 text-purple-500" />
                                    {__('admin.who_clicked')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {clicks.length === 0 ? (
                                    <div className="text-center py-4 text-slate-500 text-sm">
                                        No clicks tracked yet.
                                    </div>
                                ) : (
                                    <div className="max-h-64 overflow-y-auto pe-2 border rounded-md">
                                        <Table>
                                            <TableHeader className="bg-slate-50 sticky top-0">
                                                <TableRow>
                                                    <TableHead>User</TableHead>
                                                    <TableHead className="text-end">Time</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {clicks.map((click: any) => (
                                                    <TableRow key={click.id}>
                                                        <TableCell className="font-medium">{click.user?.name || 'Unknown'}</TableCell>
                                                        <TableCell className="text-end text-xs text-slate-500">{formatDate(click.created_at)}</TableCell>
                                                    </TableRow>
                                                ))}
                                            </TableBody>
                                        </Table>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                )}

                {/* Campaign Details */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Send className="w-5 h-5 text-indigo-500" />
                            Payload Details
                        </CardTitle>
                        <CardDescription>
                            The exact content that was delivered to the devices.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <div className="space-y-2">
                            <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                {__('general.title')}
                            </span>
                            <div className="bg-slate-50 p-4 rounded-lg border border-slate-100 font-medium text-slate-900">
                                {campaign.title}
                            </div>
                        </div>

                        <div className="space-y-2">
                            <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                {__('admin.message')}
                            </span>
                            <div className="bg-slate-50 p-4 rounded-lg border border-slate-100 text-slate-700 whitespace-pre-wrap leading-relaxed">
                                {campaign.body}
                            </div>
                        </div>

                        {campaign.target_url && (
                            <div className="space-y-2">
                                <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider flex items-center gap-1">
                                    <LinkIcon className="w-3 h-3" /> Target URL
                                </span>
                                <div className="bg-slate-50 p-3 rounded-lg border border-slate-100 text-blue-600 font-medium text-sm truncate">
                                    <a href={campaign.target_url} target="_blank" rel="noreferrer" className="hover:underline">
                                        {campaign.target_url}
                                    </a>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AdminSidebarLayout>
    );
}

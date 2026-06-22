import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Card, CardHeader, CardTitle, CardContent, CardDescription, CardFooter } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Button } from '@/Components/ui/button';
import { Textarea } from '@/Components/ui/textarea';
import { Send, AlertCircle, Users, Settings, X } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from "@/Components/ui/alert";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/Components/ui/table';
import { Badge } from '@/Components/ui/badge';
import { formatDate } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import { Link } from '@inertiajs/react';
import { DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem } from '@/Components/ui/dropdown-menu';
import { MoreHorizontal, Eye } from 'lucide-react';
import { AsyncCombobox } from '@/Components/ui/AsyncCombobox';

export default function Broadcast({ campaigns = [], roles = [] }: { campaigns?: any[], roles?: any[] }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        title: '',
        body: '',
        url: '',
        audience_type: 'global',
        personal_target: 'all',
        roles: [] as number[],
        user_ids: [] as number[],
    });

    const [selectedUsers, setSelectedUsers] = useState<any[]>([]);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.notifications.broadcast.send'), {
            onSuccess: () => {
                reset();
                setSelectedUsers([]);
            },
        });
    };

    const handleAddUser = (id: string | number | null, option: any) => {
        if (id && option) {
            if (!selectedUsers.find(u => u.id === id)) {
                const newSelected = [...selectedUsers, option];
                setSelectedUsers(newSelected);
                setData('user_ids', newSelected.map(u => u.id));
            }
        }
    };

    const handleRemoveUser = (id: number) => {
        const newSelected = selectedUsers.filter(u => u.id !== id);
        setSelectedUsers(newSelected);
        setData('user_ids', newSelected.map(u => u.id));
    };

    const handleRoleToggle = (roleId: number) => {
        if (data.roles.includes(roleId)) {
            setData('roles', data.roles.filter(id => id !== roleId));
        } else {
            setData('roles', [...data.roles, roleId]);
        }
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
                            <div className="space-y-2">
                                <Label>{__('admin.audience_type')}</Label>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div 
                                        className={`border rounded-lg p-4 cursor-pointer transition-all ${data.audience_type === 'global' ? 'border-indigo-500 bg-indigo-50' : 'hover:border-slate-300'}`}
                                        onClick={() => setData('audience_type', 'global')}
                                    >
                                        <div className="font-semibold text-slate-900 mb-1 flex items-center gap-2">
                                            <Send className="w-4 h-4" />
                                            {__('admin.global_broadcast')}
                                        </div>
                                        <div className="text-sm text-slate-500">
                                            {__('admin.global_broadcast_desc')}
                                        </div>
                                    </div>
                                    <div 
                                        className={`border rounded-lg p-4 cursor-pointer transition-all ${data.audience_type === 'personal' ? 'border-indigo-500 bg-indigo-50' : 'hover:border-slate-300'}`}
                                        onClick={() => setData('audience_type', 'personal')}
                                    >
                                        <div className="font-semibold text-slate-900 mb-1 flex items-center gap-2">
                                            <Users className="w-4 h-4" />
                                            {__('admin.personal_broadcast')}
                                        </div>
                                        <div className="text-sm text-slate-500">
                                            {__('admin.personal_broadcast_desc')}
                                        </div>
                                    </div>
                                </div>
                                {errors.audience_type && <div className="text-sm text-red-500">{errors.audience_type}</div>}
                            </div>

                            {data.audience_type === 'personal' && (
                                <div className="space-y-4 border-s-2 border-indigo-200 ps-4 py-2">
                                    <Label>{__('admin.target_users')}</Label>
                                    <div className="flex gap-2">
                                        <Button 
                                            type="button" 
                                            variant={data.personal_target === 'all' ? 'default' : 'outline'} 
                                            onClick={() => setData('personal_target', 'all')}
                                        >
                                            {__('admin.all_users')}
                                        </Button>
                                        <Button 
                                            type="button" 
                                            variant={data.personal_target === 'roles' ? 'default' : 'outline'} 
                                            onClick={() => setData('personal_target', 'roles')}
                                        >
                                            {__('admin.by_role')}
                                        </Button>
                                        <Button 
                                            type="button" 
                                            variant={data.personal_target === 'specific' ? 'default' : 'outline'} 
                                            onClick={() => setData('personal_target', 'specific')}
                                        >
                                            {__('admin.specific_users')}
                                        </Button>
                                    </div>

                                    {data.personal_target === 'roles' && (
                                        <div className="space-y-2 mt-4">
                                            <Label>{__('admin.select_roles')}</Label>
                                            <div className="flex flex-wrap gap-2">
                                                {roles?.map((role) => (
                                                    <div 
                                                        key={role.id} 
                                                        onClick={() => handleRoleToggle(role.id)}
                                                        className={`px-3 py-1.5 rounded-full border text-sm cursor-pointer transition-colors ${data.roles.includes(role.id) ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-700 hover:bg-slate-50'}`}
                                                    >
                                                        {role.name}
                                                    </div>
                                                ))}
                                            </div>
                                            {errors.roles && <div className="text-sm text-red-500">{errors.roles}</div>}
                                        </div>
                                    )}

                                    {data.personal_target === 'specific' && (
                                        <div className="space-y-3 mt-4">
                                            <Label>{__('admin.select_users')}</Label>
                                            <AsyncCombobox
                                                endpoint={route('admin.notifications.search_users')}
                                                value={null}
                                                onChange={handleAddUser}
                                                placeholder={__('admin.search_users_placeholder')}
                                            />
                                            {selectedUsers.length > 0 && (
                                                <div className="flex flex-wrap gap-2 mt-2">
                                                    {selectedUsers.map(user => (
                                                        <div key={user.id} className="flex items-center gap-1 bg-slate-100 border rounded-md px-2 py-1 text-sm">
                                                            <span>{user.name}</span>
                                                            <button 
                                                                type="button" 
                                                                onClick={() => handleRemoveUser(user.id)}
                                                                className="text-slate-500 hover:text-red-500"
                                                            >
                                                                <X className="w-3 h-3" />
                                                            </button>
                                                        </div>
                                                    ))}
                                                </div>
                                            )}
                                            {errors.user_ids && <div className="text-sm text-red-500">{errors.user_ids}</div>}
                                        </div>
                                    )}
                                </div>
                            )}

                            <div className="space-y-2 pt-4">
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
                                <Send className="w-4 h-4 me-2" />
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
                                            <TableHead>{__('admin.type')}</TableHead>
                                            <TableHead className="text-center">{__('freelance.views')}</TableHead>
                                            <TableHead>{__('general.status')}</TableHead>
                                            <TableHead className="text-end">{__('general.actions')}</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {campaigns.map((campaign) => (
                                            <TableRow key={campaign.id}>
                                                <TableCell className="font-medium">{campaign.title}</TableCell>
                                                <TableCell className="text-slate-500 text-sm">{formatDate(campaign.created_at)}</TableCell>
                                                <TableCell>
                                                    <Badge variant={campaign.audience_type === 'personal' ? 'default' : 'outline'}>
                                                        {campaign.audience_type === 'personal' ? 'Personal' : 'Global'}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    {campaign.target_url ? (
                                                        <Badge variant="secondary" className="px-3 py-1 font-bold">
                                                            {campaign.clicks_count} / {campaign.views_count}
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
                                                <TableCell className="text-end">
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button variant="ghost" className="h-8 w-8 p-0">
                                                                <span className="sr-only">{__('general.open_menu')}</span>
                                                                <MoreHorizontal className="h-4 w-4" />
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end">
                                                            <DropdownMenuItem asChild>
                                                                <Link href={route('admin.notifications.broadcast.show', campaign.id)} className="flex items-center cursor-pointer">
                                                                    <Eye className="w-4 h-4 me-2" />
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

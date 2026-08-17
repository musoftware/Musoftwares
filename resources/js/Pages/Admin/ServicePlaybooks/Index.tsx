import React, { useState } from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { BookOpen, Plus, Search, Eye, Edit3, Trash2, FileText } from 'lucide-react';
import { toast } from 'sonner';
import { __ } from '@/lib/i18n';

interface Playbook {
    id: number;
    title: string;
    marketing_message?: string;
    pricing_info?: string;
    client_requirements?: string;
    execution_workflow?: string;
    thank_you_message?: string;
    notes?: string;
    created_at: string;
    service?: {
        id: number;
        title: string;
        thumbnail?: string;
    };
    creator?: {
        id: number;
        name: string;
    };
}

interface IndexProps {
    playbooks: {
        data: Playbook[];
        links: any[];
        total: number;
    };
    filters: {
        search?: string;
    };
}

export default function Index({ playbooks, filters }: IndexProps) {
    const [search, setSearch] = useState(filters.search || '');

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/admin/marketplace/service-playbooks', { search }, { preserveState: true });
    };

    const handleDelete = (id: number, title: string) => {
        if (confirm(__('service_playbooks.delete_confirm', { title }))) {
            router.delete(`/admin/marketplace/service-playbooks/${id}`, {
                onSuccess: () => toast.success(__('service_playbooks.deleted_success')),
            });
        }
    };

    return (
        <AdminSidebarLayout header={__('service_playbooks.title')}>
            <Head title={__('service_playbooks.admin_title')} />

            <div className="max-w-7xl mx-auto space-y-6">
                {/* Header Card */}
                <Card className="border-slate-200 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white shadow-md">
                    <CardHeader className="p-6">
                        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div className="space-y-1">
                                <div className="flex items-center gap-2">
                                    <BookOpen className="w-6 h-6 text-sky-400" />
                                    <CardTitle className="text-xl font-bold text-white">
                                        {__('service_playbooks.heading')}
                                    </CardTitle>
                                </div>
                                <CardDescription className="text-slate-300 text-sm">
                                    {__('service_playbooks.description')}
                                </CardDescription>
                            </div>
                            <Link href="/admin/marketplace/service-playbooks/create">
                                <Button className="bg-sky-500 hover:bg-sky-600 text-white font-medium gap-2 shadow-sm">
                                    <Plus className="w-4 h-4" />
                                    {__('service_playbooks.create_new')}
                                </Button>
                            </Link>
                        </div>
                    </CardHeader>
                </Card>

                {/* Search Bar */}
                <div className="flex items-center justify-between gap-4">
                    <form onSubmit={handleSearch} className="relative flex-1 max-w-md">
                        <Search className="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <Input
                            type="text"
                            placeholder={__('service_playbooks.search_placeholder')}
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="pr-10 bg-white border-slate-200"
                        />
                    </form>
                    <div className="text-xs text-slate-500 font-medium">
                        {__('service_playbooks.total_playbooks', { count: playbooks.total })}
                    </div>
                </div>

                {/* Playbooks Cards List */}
                {playbooks.data.length === 0 ? (
                    <Card className="p-12 text-center border-dashed border-slate-300">
                        <FileText className="w-12 h-12 text-slate-300 mx-auto mb-3" />
                        <h3 className="text-lg font-semibold text-slate-800">{__('service_playbooks.empty_title')}</h3>
                        <p className="text-sm text-slate-500 mt-1 mb-4">
                            {__('service_playbooks.empty_desc')}
                        </p>
                        <Link href="/admin/marketplace/service-playbooks/create">
                            <Button className="bg-slate-900 hover:bg-slate-800 text-white gap-2">
                                <Plus className="w-4 h-4" />
                                {__('service_playbooks.create_playbook')}
                            </Button>
                        </Link>
                    </Card>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {playbooks.data.map((playbook) => (
                            <Card key={playbook.id} className="border-slate-200 shadow-sm hover:shadow-md transition-shadow duration-200 flex flex-col">
                                <CardHeader className="p-5 pb-3 border-b border-slate-100 bg-slate-50/50">
                                    <div className="flex items-start justify-between gap-2">
                                        <div className="space-y-1">
                                            <CardTitle className="text-base font-bold text-slate-900 line-clamp-1">
                                                {playbook.title}
                                            </CardTitle>
                                            {playbook.service ? (
                                                <Badge variant="outline" className="bg-sky-50 text-sky-700 border-sky-200 text-xs font-normal">
                                                    🔗 {playbook.service.title}
                                                </Badge>
                                            ) : (
                                                <Badge variant="outline" className="bg-slate-100 text-slate-600 border-slate-200 text-xs font-normal">
                                                    {__('service_playbooks.custom_service')}
                                                </Badge>
                                            )}
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent className="p-5 flex-1 space-y-3">
                                    <div className="text-xs text-slate-600 space-y-1.5">
                                        <div className="flex items-center justify-between">
                                            <span className="text-slate-400">{__('service_playbooks.marketing_msg')}:</span>
                                            <span className={playbook.marketing_message ? "text-emerald-600 font-medium" : "text-slate-300"}>
                                                {playbook.marketing_message ? __('service_playbooks.available') : __('service_playbooks.not_added')}
                                            </span>
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <span className="text-slate-400">{__('service_playbooks.pricing_and_packages')}:</span>
                                            <span className={playbook.pricing_info ? "text-emerald-600 font-medium" : "text-slate-300"}>
                                                {playbook.pricing_info ? __('service_playbooks.available') : __('service_playbooks.not_added')}
                                            </span>
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <span className="text-slate-400">{__('service_playbooks.client_reqs')}:</span>
                                            <span className={playbook.client_requirements ? "text-emerald-600 font-medium" : "text-slate-300"}>
                                                {playbook.client_requirements ? __('service_playbooks.available') : __('service_playbooks.not_added')}
                                            </span>
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <span className="text-slate-400">{__('service_playbooks.sop_workflow')}:</span>
                                            <span className={playbook.execution_workflow ? "text-emerald-600 font-medium" : "text-slate-300"}>
                                                {playbook.execution_workflow ? __('service_playbooks.available') : __('service_playbooks.not_added')}
                                            </span>
                                        </div>
                                    </div>
                                </CardContent>
                                <div className="p-4 border-t border-slate-100 bg-slate-50/30 flex items-center justify-between gap-2">
                                    <Link href={`/admin/marketplace/service-playbooks/${playbook.id}`}>
                                        <Button variant="outline" size="sm" className="gap-1.5 text-xs text-slate-700 hover:text-slate-900 border-slate-200">
                                            <Eye className="w-3.5 h-3.5" />
                                            {__('service_playbooks.view_and_apply')}
                                        </Button>
                                    </Link>
                                    <div className="flex items-center gap-1">
                                        <Link href={`/admin/marketplace/service-playbooks/${playbook.id}/edit`}>
                                            <Button variant="ghost" size="sm" className="h-8 w-8 p-0 text-slate-600 hover:text-slate-900" title={__('service_playbooks.edit_this')}>
                                                <Edit3 className="w-3.5 h-3.5" />
                                            </Button>
                                        </Link>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => handleDelete(playbook.id, playbook.title)}
                                            className="h-8 w-8 p-0 text-red-500 hover:text-red-700 hover:bg-red-50"
                                            title={__('service_playbooks.delete')}
                                        >
                                            <Trash2 className="w-3.5 h-3.5" />
                                        </Button>
                                    </div>
                                </div>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </AdminSidebarLayout>
    );
}

import React, { useState } from 'react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { BookOpen, Plus, Search, Eye, Edit3, Trash2, Copy, Check, FileText, Sparkles } from 'lucide-react';
import { toast } from 'sonner';

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
        if (confirm(`هل أنت تأكد من حذف دليل الخدمة "${title}"؟`)) {
            router.delete(`/admin/marketplace/service-playbooks/${id}`, {
                onSuccess: () => toast.success('تم حذف دليل الخدمة بنجاح'),
            });
        }
    };

    return (
        <AdminSidebarLayout header="أدلة تقديم الخدمات (Service Playbooks)">
            <Head title="أدلة تقديم الخدمات - Admin" />

            <div className="max-w-7xl mx-auto space-y-6">
                {/* Header Card */}
                <Card className="border-slate-200 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white shadow-md">
                    <CardHeader className="p-6">
                        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div className="space-y-1">
                                <div className="flex items-center gap-2">
                                    <BookOpen className="w-6 h-6 text-sky-400" />
                                    <CardTitle className="text-xl font-bold text-white">
                                        أدلة تقديم الخدمات والكيفية التنفيذية (Service Playbooks)
                                    </CardTitle>
                                </div>
                                <CardDescription className="text-slate-300 text-sm">
                                    سجل مرجعي داخلي وشامل لكيفية تنفيذ الخدمات، الرسائل التسويقية، المطلوبة من العميل، والأسعار الجاهزة للنسخ السريع.
                                </CardDescription>
                            </div>
                            <Link href="/admin/marketplace/service-playbooks/create">
                                <Button className="bg-sky-500 hover:bg-sky-600 text-white font-medium gap-2 shadow-sm">
                                    <Plus className="w-4 h-4" />
                                    إضافة دليل خدمة جديد
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
                            placeholder="بحث باسم دليل الخدمة أو اسم الخدمة..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="pr-10 bg-white border-slate-200"
                        />
                    </form>
                    <div className="text-xs text-slate-500 font-medium">
                        إجمالي الأدلة: <span className="font-bold text-slate-900">{playbooks.total}</span>
                    </div>
                </div>

                {/* Playbooks Cards List */}
                {playbooks.data.length === 0 ? (
                    <Card className="p-12 text-center border-dashed border-slate-300">
                        <FileText className="w-12 h-12 text-slate-300 mx-auto mb-3" />
                        <h3 className="text-lg font-semibold text-slate-800">لا توجد أدلة خدمات مسجلة حتى الآن</h3>
                        <p className="text-sm text-slate-500 mt-1 mb-4">
                            قم بإنشاء أول دليل خدمة لتسهيل التواصل والعمل مع العملاء.
                        </p>
                        <Link href="/admin/marketplace/service-playbooks/create">
                            <Button className="bg-slate-900 hover:bg-slate-800 text-white gap-2">
                                <Plus className="w-4 h-4" />
                                إنشاء دليل خدمة
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
                                                    خدمة مخصصة
                                                </Badge>
                                            )}
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent className="p-5 flex-1 space-y-3">
                                    <div className="text-xs text-slate-600 space-y-1.5">
                                        <div className="flex items-center justify-between">
                                            <span className="text-slate-400">الرسالة التسويقية:</span>
                                            <span className={playbook.marketing_message ? "text-emerald-600 font-medium" : "text-slate-300"}>
                                                {playbook.marketing_message ? "✓ متوفرة" : "غير مضافة"}
                                            </span>
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <span className="text-slate-400">الأسعار والباقات:</span>
                                            <span className={playbook.pricing_info ? "text-emerald-600 font-medium" : "text-slate-300"}>
                                                {playbook.pricing_info ? "✓ متوفرة" : "غير مضافة"}
                                            </span>
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <span className="text-slate-400">المطلوب من العميل:</span>
                                            <span className={playbook.client_requirements ? "text-emerald-600 font-medium" : "text-slate-300"}>
                                                {playbook.client_requirements ? "✓ متوفرة" : "غير مضافة"}
                                            </span>
                                        </div>
                                        <div className="flex items-center justify-between">
                                            <span className="text-slate-400">خطوات التنفيذ (SOP):</span>
                                            <span className={playbook.execution_workflow ? "text-emerald-600 font-medium" : "text-slate-300"}>
                                                {playbook.execution_workflow ? "✓ متوفرة" : "غير مضافة"}
                                            </span>
                                        </div>
                                    </div>
                                </CardContent>
                                <div className="p-4 border-t border-slate-100 bg-slate-50/30 flex items-center justify-between gap-2">
                                    <Link href={`/admin/marketplace/service-playbooks/${playbook.id}`}>
                                        <Button variant="outline" size="sm" className="gap-1.5 text-xs text-slate-700 hover:text-slate-900 border-slate-200">
                                            <Eye className="w-3.5 h-3.5" />
                                            عرض وتطبيق
                                        </Button>
                                    </Link>
                                    <div className="flex items-center gap-1">
                                        <Link href={`/admin/marketplace/service-playbooks/${playbook.id}/edit`}>
                                            <Button variant="ghost" size="sm" className="h-8 w-8 p-0 text-slate-600 hover:text-slate-900">
                                                <Edit3 className="w-3.5 h-3.5" />
                                            </Button>
                                        </Link>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => handleDelete(playbook.id, playbook.title)}
                                            className="h-8 w-8 p-0 text-red-500 hover:text-red-700 hover:bg-red-50"
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

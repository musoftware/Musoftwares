import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import Pagination from '@/Components/Pagination';
import { Badge } from '@/Components/ui/badge';
import { 
    ExternalLink, 
    Search, 
    FileText, 
    Plus, 
    MoreHorizontal, 
    Pencil, 
    Trash2, 
    Sparkles, 
    AlertCircle, 
    RefreshCw, 
    CheckCircle2 
} from 'lucide-react';
import { Dialog, DialogTrigger, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { __ } from '@/lib/i18n';

interface Service {
    id: number;
    title: string;
    articles_en_count: number;
    articles_ar_count: number;
    seller?: {
        name: string;
    };
}

interface IndexProps {
    articles: {
        data: any[];
        links: any[];
        total: number;
    };
    services: Service[];
    filters: {
        q?: string;
    };
}

export default function Index({ articles, services, filters }: IndexProps) {
    const [activeTab, setActiveTab] = useState<'articles' | 'services'>('articles');
    const [search, setSearch] = useState(filters.q || '');
    
    // Auto-blog generation state
    const [generatingId, setGeneratingId] = useState<number | null>(null);
    const [selectedService, setSelectedService] = useState<Service | null>(null);
    const [genLang, setGenLang] = useState<string>('all');
    const [isDialogOpen, setIsDialogOpen] = useState<boolean>(false);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(route('admin.blog-articles.index'), { q: search }, { preserveState: true });
    };

    const handleGenerate = (serviceId: number, lang: string) => {
        setGeneratingId(serviceId);
        setIsDialogOpen(false);
        router.post(route('admin.blog-articles.generate'), {
            service_id: serviceId,
            lang: lang
        }, {
            onFinish: () => {
                setGeneratingId(null);
            }
        });
    };

    // Calculate metrics
    const totalServices = services?.length || 0;
    const servicesWithoutPosts = services?.filter(s => s.articles_en_count === 0 && s.articles_ar_count === 0) || [];
    const servicesWithoutPostsCount = servicesWithoutPosts.length;
    const totalArticles = articles.total || 0;

    const getServiceStatusBadge = (enCount: number, arCount: number) => {
        if (enCount > 0 && arCount > 0) {
            return (
                <Badge className="bg-green-50 text-green-700 border-green-200 hover:bg-green-50 border shadow-none">
                    <CheckCircle2 className="h-3 w-3 me-1 text-green-600 inline" />
                    {__('general.completed') || 'Completed'}
                </Badge>
            );
        }
        if (enCount > 0 || arCount > 0) {
            return (
                <Badge className="bg-yellow-50 text-yellow-700 border-yellow-250 hover:bg-yellow-50 border shadow-none">
                    <AlertCircle className="h-3 w-3 me-1 text-yellow-600 inline" />
                    {__('general.partial') || 'Partial'}
                </Badge>
            );
        }
        return (
            <Badge variant="destructive" className="bg-red-50 text-red-700 border-red-200 hover:bg-red-50 border shadow-none">
                <AlertCircle className="h-3 w-3 me-1 text-red-600 inline" />
                {__('general.missing_posts') || 'No Posts Yet'}
            </Badge>
        );
    };

    return (
        <AdminSidebarLayout title={__('general.blog_articles')} header="Blog Articles Manager">
            
            {/* Stats Cards */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div className="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                    <div>
                        <p className="text-xs font-semibold text-gray-400 uppercase tracking-wider">{__('general.total_services') || 'Active Services'}</p>
                        <h3 className="text-3xl font-extrabold text-gray-900 mt-2">{totalServices}</h3>
                    </div>
                    <div className="h-12 w-12 rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-600">
                        <FileText className="h-6 w-6" />
                    </div>
                </div>

                <div className="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                    <div>
                        <p className="text-xs font-semibold text-gray-400 uppercase tracking-wider">{__('general.services_without_posts') || 'Services Without Posts'}</p>
                        <h3 className="text-3xl font-extrabold mt-2 text-gray-900 flex items-center gap-2">
                            {servicesWithoutPostsCount}
                            {servicesWithoutPostsCount > 0 && (
                                <Badge variant="destructive" className="bg-red-50 text-red-700 hover:bg-red-50 border-red-250 border shadow-none font-bold text-xs">
                                    {__('general.needs_attention') || 'Needs Attention'}
                                </Badge>
                            )}
                        </h3>
                    </div>
                    <div className={`h-12 w-12 rounded-lg flex items-center justify-center border ${
                        servicesWithoutPostsCount > 0 
                            ? 'bg-red-50 border-red-205 text-red-600' 
                            : 'bg-green-50 border-green-200 text-green-600'
                    }`}>
                        <AlertCircle className="h-6 w-6" />
                    </div>
                </div>

                <div className="bg-white p-6 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
                    <div>
                        <p className="text-xs font-semibold text-gray-400 uppercase tracking-wider">{__('general.total_blog_articles') || 'Total Generated Articles'}</p>
                        <h3 className="text-3xl font-extrabold text-gray-900 mt-2">{totalArticles}</h3>
                    </div>
                    <div className="h-12 w-12 rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-center text-gray-600">
                        <ExternalLink className="h-6 w-6" />
                    </div>
                </div>
            </div>

            {/* State Tabs */}
            <div className="flex border-b border-gray-200 mb-6 gap-6">
                <button
                    onClick={() => setActiveTab('articles')}
                    className={`pb-3 text-sm font-semibold border-b-2 transition-all ${
                        activeTab === 'articles'
                            ? 'border-black text-black'
                            : 'border-transparent text-gray-400 hover:text-black'
                    }`}
                >
                    {__('general.blog_articles') || 'Blog Articles'}
                </button>
                <button
                    onClick={() => setActiveTab('services')}
                    className={`pb-3 text-sm font-semibold border-b-2 transition-all ${
                        activeTab === 'services'
                            ? 'border-black text-black'
                            : 'border-transparent text-gray-400 hover:text-black'
                    }`}
                >
                    {__('general.services_auto_blog') || 'Services & Auto-Blog'}
                </button>
            </div>

            {activeTab === 'articles' && (
                <>
                    <div className="mb-6 flex items-center justify-end gap-4">
                        <form onSubmit={handleSearch} className="flex max-w-sm w-full relative">
                            <Input
                                type="text"
                                placeholder={__('general.search_articles_by_title_or_content')}
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="ps-10 h-10"
                            />
                            <Search className="absolute start-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <Button type="submit" className="ms-2 h-10">{__('general.search')}</Button>
                        </form>
                        <Link href={route('admin.blog-articles.create')}>
                            <Button className="h-10 gap-2">
                                <Plus className="h-4 w-4" />{__('general.add_article')}</Button>
                        </Link>
                    </div>

                    <div className="overflow-hidden rounded-lg bg-white shadow border border-gray-200">
                        <div className="overflow-x-auto">
                            <table className="w-full text-start text-sm whitespace-nowrap">
                                <thead className="border-b bg-gray-50">
                                    <tr>
                                        <th className="p-4 font-medium text-gray-600 w-16">ID</th>
                                        <th className="p-4 font-medium text-gray-600">{__('general.title')}</th>
                                        <th className="p-4 font-medium text-gray-600 text-center">{__('general.language')}</th>
                                        <th className="p-4 font-medium text-gray-600 text-center">{__('general.status')}</th>
                                        <th className="p-4 font-medium text-gray-600 text-center">{__('general.date')}</th>
                                        <th className="p-4 font-medium text-gray-600">{__('general.owner')}</th>
                                        <th className="p-4 font-medium text-gray-600 text-end">{__('general.actions')}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {(articles.data as any).map((article) => (
                                        <tr key={article.id} className="border-b hover:bg-gray-50 transition-colors">
                                            <td className="p-4 font-medium text-gray-900">#{article.id}</td>
                                            <td className="p-4">
                                                <a href={route('blog.show', { slug: article.slug })} target="_blank" rel="noopener noreferrer" className="hover:underline font-semibold text-gray-800">
                                                    {article.title?.length > 50 ? article.title.substring(0, 50) + '...' : article.title}
                                                </a>
                                            </td>
                                            <td className="p-4 text-center">
                                                <Badge variant="secondary" className="uppercase font-bold tracking-wider">
                                                    {article.language || 'en'}
                                                </Badge>
                                            </td>
                                            <td className="p-4 text-center">
                                                {article.is_published ? (
                                                    <Badge variant="default" className="bg-green-100 text-green-800 hover:bg-green-200 border-none shadow-none">
                                                        {__('general.published')}</Badge>
                                                ) : (
                                                    <Badge variant="secondary" className="bg-yellow-100 text-yellow-800 hover:bg-yellow-200 border-none shadow-none">
                                                        {__('general.draft')}</Badge>
                                                )}
                                            </td>
                                            <td className="p-4 text-center text-gray-500">
                                                {new Date(article.published_at || article.created_at).toISOString().split('T')[0]}
                                            </td>
                                            <td className="p-4">
                                                {article.service?.seller ? (
                                                    <span className="font-medium">{article.service.seller.name}</span>
                                                ) : (
                                                    <span className="text-gray-400 italic text-sm">{__('general.system_unknown')}</span>
                                                )}
                                            </td>
                                            <td className="p-4 text-end">
                                                <Dialog>
                                                    <DialogTrigger asChild>
                                                        <Button variant="ghost" className="h-8 w-8 p-0">
                                                            <span className="sr-only">{__('general.open_menu')}</span>
                                                            <MoreHorizontal className="h-4 w-4" />
                                                        </Button>
                                                    </DialogTrigger>
                                                    <DialogContent className="sm:max-w-xs">
                                                        <DialogHeader>
                                                            <DialogTitle>{__('general.actions')}</DialogTitle>
                                                        </DialogHeader>
                                                        <div className="flex flex-col gap-2 py-2">
                                                            <a href={route('blog.show', { slug: article.slug })} target="_blank" rel="noopener noreferrer" className="w-full">
                                                                <Button variant="outline" className="w-full justify-start gap-2">
                                                                    <ExternalLink className="h-4 w-4" />
                                                                    {__('general.view')}</Button>
                                                            </a>
                                                            <Link href={route('admin.blog-articles.edit', article.id)} className="w-full">
                                                                <Button variant="outline" className="w-full justify-start gap-2">
                                                                    <Pencil className="h-4 w-4" />
                                                                    {__('general.edit')}</Button>
                                                            </Link>
                                                            <Link href={route('admin.blog-articles.destroy', article.id)} method="delete" as="button" className="w-full">
                                                                <Button variant="destructive" className="w-full justify-start gap-2">
                                                                    <Trash2 className="h-4 w-4" />
                                                                    {__('general.delete')}</Button>
                                                            </Link>
                                                        </div>
                                                    </DialogContent>
                                                </Dialog>
                                            </td>
                                        </tr>
                                    ))}
                                    {(articles.data as any).length === 0 && (
                                        <tr>
                                            <td colSpan={7} className="p-12 text-center text-gray-500">
                                                <div className="flex flex-col items-center justify-center">
                                                    <FileText className="h-10 w-10 text-gray-300 mb-3" />
                                                    <p className="text-lg font-medium text-gray-600">{__('general.no_articles_found')}</p>
                                                    <p className="text-sm text-gray-400 mt-1">{__('general.try_adjusting_your_search_query')}</p>
                                                </div>
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <Pagination links={articles.links} />
                </>
            )}

            {activeTab === 'services' && (
                <div className="overflow-hidden rounded-lg bg-white shadow border border-gray-200">
                    <div className="overflow-x-auto">
                        <table className="w-full text-start text-sm whitespace-nowrap">
                            <thead className="border-b bg-gray-50">
                                <tr>
                                    <th className="p-4 font-medium text-gray-600 w-16">ID</th>
                                    <th className="p-4 font-medium text-gray-600">{__('general.service_title') || 'Service Title'}</th>
                                    <th className="p-4 font-medium text-gray-600 text-center">{__('general.english_posts') || 'English Posts'}</th>
                                    <th className="p-4 font-medium text-gray-600 text-center">{__('general.arabic_posts') || 'Arabic Posts'}</th>
                                    <th className="p-4 font-medium text-gray-600 text-center">{__('general.status') || 'Status'}</th>
                                    <th className="p-4 font-medium text-gray-600 text-end">{__('general.actions') || 'Actions'}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {services?.map((service) => (
                                    <tr key={service.id} className="border-b hover:bg-gray-50 transition-colors">
                                        <td className="p-4 font-medium text-gray-900">#{service.id}</td>
                                        <td className="p-4">
                                            <span className="font-semibold text-gray-800">
                                                {service.title}
                                            </span>
                                        </td>
                                        <td className="p-4 text-center">
                                            {service.articles_en_count > 0 ? (
                                                <Badge variant="secondary" className="bg-indigo-50 text-indigo-700 border-indigo-100 border shadow-none font-bold">
                                                    {service.articles_en_count} {__('general.posts') || 'posts'}
                                                </Badge>
                                            ) : (
                                                <Badge variant="outline" className="text-gray-400 border-gray-200 border shadow-none">
                                                    0 {__('general.posts') || 'posts'}
                                                </Badge>
                                            )}
                                        </td>
                                        <td className="p-4 text-center">
                                            {service.articles_ar_count > 0 ? (
                                                <Badge variant="secondary" className="bg-indigo-50 text-indigo-700 border-indigo-100 border shadow-none font-bold">
                                                    {service.articles_ar_count} {__('general.posts') || 'posts'}
                                                </Badge>
                                            ) : (
                                                <Badge variant="outline" className="text-gray-400 border-gray-200 border shadow-none">
                                                    0 {__('general.posts') || 'posts'}
                                                </Badge>
                                            )}
                                        </td>
                                        <td className="p-4 text-center">
                                            {getServiceStatusBadge(service.articles_en_count, service.articles_ar_count)}
                                        </td>
                                        <td className="p-4 text-end">
                                            <Button 
                                                variant="outline" 
                                                size="sm" 
                                                className="gap-2 h-8 border-gray-250 hover:bg-gray-50 hover:border-gray-300 font-semibold"
                                                onClick={() => {
                                                    setSelectedService(service);
                                                    setIsDialogOpen(true);
                                                }}
                                                disabled={generatingId === service.id}
                                            >
                                                {generatingId === service.id ? (
                                                    <>
                                                        <RefreshCw className="h-3.5 w-3.5 animate-spin text-gray-500" />
                                                        {__('general.generating') || 'Generating...'}
                                                    </>
                                                ) : (
                                                    <>
                                                        <Sparkles className="h-3.5 w-3.5 text-indigo-600" />
                                                        {__('general.generate_ai_post') || 'Generate AI Post'}
                                                    </>
                                                )}
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                                {services?.length === 0 && (
                                    <tr>
                                        <td colSpan={6} className="p-12 text-center text-gray-500">
                                            <div className="flex flex-col items-center justify-center">
                                                <FileText className="h-10 w-10 text-gray-300 mb-3" />
                                                <p className="text-lg font-medium text-gray-600">{__('general.no_services_found') || 'No Active Services Found'}</p>
                                                <p className="text-sm text-gray-400 mt-1">{__('general.create_active_services_to_enable') || 'Ensure you have active services in the marketplace first.'}</p>
                                            </div>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {/* Auto-Generation Dialog */}
            <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Sparkles className="h-5 w-5 text-indigo-600" />
                            <span>{__('general.generate_ai_blog_post') || 'Generate AI Blog Post'}</span>
                        </DialogTitle>
                    </DialogHeader>
                    {selectedService && (
                        <div className="py-4 space-y-4">
                            <p className="text-sm text-gray-500 leading-relaxed">
                                {__('general.generate_for_service') || 'Select the language(s) to generate a new blog article for service:'}{' '}
                                <strong className="text-gray-950 font-bold">{selectedService.title}</strong>.
                            </p>
                            <div className="flex flex-col gap-2">
                                <label className="text-xs font-bold text-gray-500 uppercase tracking-wider">{__('general.generation_language') || 'Language'}</label>
                                <select 
                                    value={genLang} 
                                    onChange={(e) => setGenLang(e.target.value)}
                                    className="w-full p-2.5 rounded-lg border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-500 font-medium"
                                >
                                    <option value="all">{__('general.both_ar_en') || 'Both English & Arabic'}</option>
                                    <option value="en">{__('general.english') || 'English'}</option>
                                    <option value="ar">{__('general.arabic') || 'Arabic'}</option>
                                </select>
                            </div>
                            <div className="flex justify-end gap-3 pt-4 border-t border-gray-150">
                                <Button variant="ghost" onClick={() => setIsDialogOpen(false)}>{__('general.cancel')}</Button>
                                <Button onClick={() => handleGenerate(selectedService.id, genLang)} className="gap-2">
                                    <Sparkles className="h-4 w-4" />
                                    {__('general.generate') || 'Generate'}
                                </Button>
                            </div>
                        </div>
                    )}
                </DialogContent>
            </Dialog>

        </AdminSidebarLayout>
    );
}

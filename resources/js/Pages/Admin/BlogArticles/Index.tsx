import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import Pagination from '@/Components/Pagination';
import { Badge } from '@/Components/ui/badge';
import { ExternalLink, Search, FileText, Plus, MoreHorizontal, Pencil, Trash2 } from 'lucide-react';
import { Dialog, DialogTrigger, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { __ } from '@/lib/i18n';
export default function Index({ articles, filters }) {
    const [search, setSearch] = useState(filters.q || '');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('admin.blog-articles.index'), { q: search }, { preserveState: true });
    };

    return (
        <AdminSidebarLayout title={__('general.blog_articles')} header="Blog Articles Manager">
            <div className="mb-6 flex items-center justify-between">
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
                                        {new Date(article.created_at).toISOString().split('T')[0]}
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
        </AdminSidebarLayout>
    );
}

import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminSidebarLayout from '@/Layouts/AdminSidebarLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import Pagination from '@/Components/Pagination';
import { Badge } from '@/Components/ui/badge';
import { ExternalLink, Search, FileText } from 'lucide-react';

export default function Index({ articles, filters }) {
    const [search, setSearch] = useState(filters.q || '');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('admin.blog-articles.index'), { q: search }, { preserveState: true });
    };

    return (
        <AdminSidebarLayout title="Blog Articles" header="Blog Articles Manager">
            <div className="mb-6 flex items-center justify-between">
                <form onSubmit={handleSearch} className="flex max-w-sm w-full relative">
                    <Input
                        type="text"
                        placeholder="Search articles by title or content..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="pl-10 h-10"
                    />
                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                    <Button type="submit" className="ml-2 h-10">Search</Button>
                </form>
            </div>

            <div className="overflow-hidden rounded-lg bg-white shadow border border-gray-200">
                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm whitespace-nowrap">
                        <thead className="border-b bg-gray-50">
                            <tr>
                                <th className="p-4 font-medium text-gray-600 w-16">ID</th>
                                <th className="p-4 font-medium text-gray-600">Title</th>
                                <th className="p-4 font-medium text-gray-600 text-center">Language</th>
                                <th className="p-4 font-medium text-gray-600 text-center">Status</th>
                                <th className="p-4 font-medium text-gray-600 text-center">Date</th>
                                <th className="p-4 font-medium text-gray-600">Owner</th>
                                <th className="p-4 font-medium text-gray-600 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {articles.data.map((article) => (
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
                                                Published
                                            </Badge>
                                        ) : (
                                            <Badge variant="secondary" className="bg-yellow-100 text-yellow-800 hover:bg-yellow-200 border-none shadow-none">
                                                Draft
                                            </Badge>
                                        )}
                                    </td>
                                    <td className="p-4 text-center text-gray-500">
                                        {new Date(article.created_at).toISOString().split('T')[0]}
                                    </td>
                                    <td className="p-4">
                                        {article.service?.seller ? (
                                            <span className="font-medium">{article.service.seller.name}</span>
                                        ) : (
                                            <span className="text-gray-400 italic text-sm">System/Unknown</span>
                                        )}
                                    </td>
                                    <td className="p-4 text-right">
                                        <a href={route('blog.show', { slug: article.slug })} target="_blank" rel="noopener noreferrer">
                                            <Button variant="ghost" size="sm" className="gap-2 text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50">
                                                <ExternalLink className="h-4 w-4" />
                                                View
                                            </Button>
                                        </a>
                                    </td>
                                </tr>
                            ))}
                            {articles.data.length === 0 && (
                                <tr>
                                    <td colSpan={7} className="p-12 text-center text-gray-500">
                                        <div className="flex flex-col items-center justify-center">
                                            <FileText className="h-10 w-10 text-gray-300 mb-3" />
                                            <p className="text-lg font-medium text-gray-600">No articles found.</p>
                                            <p className="text-sm text-gray-400 mt-1">Try adjusting your search query.</p>
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

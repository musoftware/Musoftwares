import React from 'react';
import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { motion } from 'framer-motion';
import { Calendar, User, ArrowRight, BookOpen, Globe } from 'lucide-react';
import { __ } from '@/lib/i18n';

interface BlogArticle {
    id: number;
    title: string;
    slug: string;
    excerpt: string;
    featured_image?: string;
    language?: string;
    created_at: string;
    published_at: string;
    service?: {
        seller?: {
            name: string;
        };
    };
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedData {
    data: BlogArticle[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
}

interface IndexProps {
    articles: PaginatedData;
}

export default function Index({ articles }: IndexProps) {
    return (
        <PublicLayout>
            <Head>
                <title>Blog — musoftware</title>
                <meta name="description" content="Read the latest insights, tutorials, and updates from the Musoftware team." />
            </Head>

            <div className="relative min-h-screen py-16 lg:py-24 bg-slate-50 overflow-hidden">
                {/* Background Decorations */}
                <div className="absolute top-0 left-1/4 w-96 h-96 bg-indigo-200/30 rounded-full blur-3xl pointer-events-none z-0" />
                <div className="absolute top-1/3 right-1/4 w-[400px] h-[400px] bg-purple-200/20 rounded-full blur-3xl pointer-events-none z-0" />

                <div className="max-w-7xl mx-auto px-6 relative z-10">
                    
                    {/* Hero Section */}
                    <div className="text-center max-w-3xl mx-auto mb-16 lg:mb-24">
                        <motion.div 
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.5 }}
                            className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-indigo-100/50 text-indigo-700 text-sm font-medium mb-6"
                        >
                            <BookOpen className="h-4 w-4" />
                            <span>{__('general.our_blog') || 'Our Blog'}</span>
                        </motion.div>

                        <motion.h1 
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.5, delay: 0.1 }}
                            className="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-slate-900 mb-6"
                        >
                            Insights, tutorials & updates
                        </motion.h1>

                        <motion.p 
                            initial={{ opacity: 0, y: 20 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.5, delay: 0.2 }}
                            className="text-lg sm:text-xl text-slate-600 font-light"
                        >
                            Discover the latest ideas and best practices in modern software development, business management, and digital transformation.
                        </motion.p>
                    </div>

                    {/* Articles Grid */}
                    {articles.data.length > 0 ? (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                            {articles.data.map((article, index) => (
                                <motion.div
                                    key={article.id}
                                    initial={{ opacity: 0, y: 30 }}
                                    animate={{ opacity: 1, y: 0 }}
                                    transition={{ duration: 0.5, delay: index * 0.1 }}
                                    className="group flex flex-col bg-white rounded-3xl overflow-hidden border border-slate-200/60 shadow-sm hover:shadow-xl hover:border-indigo-100 transition-all duration-300 h-full"
                                >
                                    <Link href={`/blog/${article.slug}`} className="relative aspect-[16/10] overflow-hidden bg-slate-100 block">
                                        {article.featured_image ? (
                                            <img 
                                                src={article.featured_image.startsWith('http') ? article.featured_image : `/storage/${article.featured_image}`} 
                                                alt={article.title} 
                                                className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" 
                                            />
                                        ) : (
                                            <div className="w-full h-full flex items-center justify-center bg-indigo-50 text-indigo-200">
                                                <BookOpen className="h-16 w-16 opacity-50" />
                                            </div>
                                        )}
                                        
                                        {article.language && (
                                            <div className="absolute top-4 left-4">
                                                <Badge className="bg-white/90 backdrop-blur-sm text-slate-800 hover:bg-white uppercase font-bold tracking-wider border-none shadow-sm">
                                                    <Globe className="h-3 w-3 mr-1 inline" />
                                                    {article.language}
                                                </Badge>
                                            </div>
                                        )}
                                    </Link>

                                    <div className="flex flex-col flex-grow p-6 sm:p-8">
                                        <div className="flex items-center gap-4 text-xs text-slate-400 mb-4 font-medium uppercase tracking-wider">
                                            <span className="flex items-center gap-1.5">
                                                <Calendar className="h-3.5 w-3.5" />
                                                {new Date(article.published_at || article.created_at).toLocaleDateString('en-US', {
                                                    month: 'short',
                                                    day: 'numeric',
                                                    year: 'numeric'
                                                })}
                                            </span>
                                        </div>

                                        <Link href={`/blog/${article.slug}`} className="block mb-3 flex-grow">
                                            <h3 className="text-xl sm:text-2xl font-bold text-slate-900 leading-tight group-hover:text-indigo-600 transition-colors line-clamp-3">
                                                {article.title}
                                            </h3>
                                        </Link>

                                        <p className="text-slate-500 text-sm leading-relaxed mb-6 line-clamp-3">
                                            {article.excerpt}
                                        </p>

                                        <div className="flex items-center justify-between mt-auto pt-4 border-t border-slate-100">
                                            <div className="flex items-center gap-2.5">
                                                <div className="h-8 w-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100">
                                                    <User className="h-4 w-4" />
                                                </div>
                                                <span className="text-sm font-semibold text-slate-700">
                                                    {article.service?.seller?.name || 'System Writer'}
                                                </span>
                                            </div>

                                            <Link href={`/blog/${article.slug}`}>
                                                <Button variant="ghost" size="icon" className="h-8 w-8 rounded-full text-indigo-600 hover:bg-indigo-50 hover:text-indigo-700">
                                                    <ArrowRight className="h-4 w-4" />
                                                </Button>
                                            </Link>
                                        </div>
                                    </div>
                                </motion.div>
                            ))}
                        </div>
                    ) : (
                        <div className="bg-white rounded-3xl p-12 text-center border border-slate-200/60 shadow-sm max-w-2xl mx-auto">
                            <div className="h-20 w-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6">
                                <BookOpen className="h-10 w-10 text-slate-300" />
                            </div>
                            <h3 className="text-2xl font-bold text-slate-900 mb-2">No articles found</h3>
                            <p className="text-slate-500">We're currently working on creating amazing content for you. Check back soon!</p>
                        </div>
                    )}

                    {/* Pagination */}
                    {articles.links.length > 3 && (
                        <div className="mt-16 flex justify-center">
                            <div className="flex flex-wrap justify-center gap-2">
                                {articles.links.map((link, i) => (
                                    <Link
                                        key={i}
                                        href={link.url || '#'}
                                        preserveScroll
                                        className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
                                            link.active 
                                                ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' 
                                                : !link.url 
                                                    ? 'bg-slate-50 text-slate-400 cursor-not-allowed border border-slate-200' 
                                                    : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200 hover:border-slate-300'
                                        }`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        </div>
                    )}

                </div>
            </div>
        </PublicLayout>
    );
}

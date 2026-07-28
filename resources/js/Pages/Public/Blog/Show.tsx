import React, { useEffect, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { motion, useScroll, useSpring } from 'framer-motion';
import { ArrowLeft, ArrowRight, Calendar, User, Clock, Globe, Share2, Bookmark } from 'lucide-react';
import { marked } from 'marked';
import DOMPurify from 'dompurify';
import { __ } from '@/lib/i18n';
import { formatMoney as formatCurrency } from '@/lib/utils';

interface ServicePackage {
    id: number;
    name: string;
    price: number;
    currency?: {
        symbol: string;
        currency: string;
    };
}

interface BlogArticle {
    id: number;
    title: string;
    slug: string;
    content: string;
    excerpt: string;
    featured_image?: string;
    language?: string;
    created_at: string;
    service_id?: number;
    service?: {
        id: number;
        title: string;
        slug: string;
        cover_image?: string;
        thumbnail?: string;
        url?: string;
        is_free?: boolean;
        seller?: {
            name: string;
        };
        packages?: ServicePackage[];
    };
}

interface ShowProps {
    article: BlogArticle;
}

export default function Show({ article }: ShowProps) {
    const [cleanHtml, setCleanHtml] = useState('');
    const [readTime, setReadTime] = useState(0);

    // Scroll progress bar logic using framer-motion
    const { scrollYProgress } = useScroll();
    const scaleX = useSpring(scrollYProgress, {
        stiffness: 100,
        damping: 30,
        restDelta: 0.001
    });

    useEffect(() => {
        if (article.content) {
            // Parse Markdown content to HTML and sanitize it
            const parsed = marked.parse(article.content);
            const sanitized = DOMPurify.sanitize(parsed as string);
            setCleanHtml(sanitized);

            // Calculate estimated reading time (approx 200 words per minute)
            const wordCount = article.content.split(/\s+/).length;
            const minutes = Math.ceil(wordCount / 200);
            setReadTime(minutes);
        }
    }, [article.content]);

    const handleShare = () => {
        if (navigator.share) {
            navigator.share({
                title: article.title,
                text: article.excerpt,
                url: window.location.href,
            }).catch(console.error);
        } else {
            // Fallback: Copy to clipboard
            navigator.clipboard.writeText(window.location.href);
            alert('Article link copied to clipboard!');
        }
    };

    return (
        <PublicLayout>
            <Head>
                <title>{`${article?.title || ''} — musoftware Blog`}</title>
                <meta name="description" content={article?.excerpt || article?.title || ''} />
            </Head>

            {/* Reading progress indicator */}
            <motion.div
                className="fixed top-0 start-0 end-0 h-1 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 origin-[0%] z-[60]"
                style={{ scaleX }}
            />

            <div className="relative min-h-screen py-16 lg:py-24">
                {/* Visual decorations/gradients */}
                <div className="absolute top-0 start-1/4 w-96 h-96 bg-indigo-200/30 rounded-full blur-3xl pointer-events-none z-0" />
                <div className="absolute top-1/3 end-1/4 w-[400px] h-[400px] bg-purple-200/20 rounded-full blur-3xl pointer-events-none z-0" />

                <div className="max-w-7xl mx-auto px-6 relative z-10">
                    {/* Back Button */}
                    <div className="mb-8">
                        <Link href="/blog">
                            <Button variant="ghost" className="gap-2 text-slate-500 hover:text-slate-900 transition-colors">
                                <ArrowLeft className="h-4 w-4" />{__('general.back_to_articles')}</Button>
                        </Link>
                    </div>

                    <motion.article 
                        initial={{ opacity: 0, y: 30 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.6, ease: [0.16, 1, 0.3, 1] }}
                    >
                        {/* Article Header Details */}
                        <div className="space-y-6 mb-8 text-center sm:text-start">
                            <div className="flex flex-wrap items-center justify-center sm:justify-start gap-3">
                                <Badge variant="secondary" className="uppercase font-bold tracking-wider px-3 py-1 bg-indigo-50 text-indigo-700 hover:bg-indigo-100/80 border-none">
                                    <Globe className="h-3 w-3 me-1 inline" />
                                    {article.language || 'en'}
                                </Badge>
                                <span className="text-slate-400 text-sm flex items-center">
                                    <Clock className="h-3.5 w-3.5 me-1" />
                                    {readTime} min read
                                </span>
                            </div>

                            <h1 className="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-slate-900 leading-tight">
                                {article.title}
                            </h1>

                            <p className="text-lg text-slate-500 font-light leading-relaxed max-w-3xl">
                                {article.excerpt}
                            </p>

                            {/* Author & Meta details */}
                            <div className="flex flex-col sm:flex-row items-center justify-between border-y border-slate-100 py-4 gap-4">
                                <div className="flex items-center gap-3">
                                    <div className="h-10 w-10 rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600">
                                        <User className="h-5 w-5" />
                                    </div>
                                    <div className="text-start">
                                        <span className="block text-sm font-semibold text-slate-800">
                                            {article.service?.seller?.name || 'System Writer'}
                                        </span>
                                        <span className="block text-xs text-slate-400">{__('general.published_content_creator')}</span>
                                    </div>
                                </div>

                                <div className="flex items-center gap-4 text-sm text-slate-400">
                                    <span className="flex items-center gap-1.5">
                                        <Calendar className="h-4 w-4" />
                                        {new Date(article.created_at).toLocaleDateString('en-US', {
                                            year: 'numeric',
                                            month: 'long',
                                            day: 'numeric',
                                        })}
                                    </span>
                                    <div className="flex gap-2">
                                        <Button variant="outline" size="icon" className="h-8 w-8 rounded-full border-slate-200 text-slate-500 hover:text-slate-900" onClick={handleShare}>
                                            <Share2 className="h-3.5 w-3.5" />
                                        </Button>
                                        <Button variant="outline" size="icon" className="h-8 w-8 rounded-full border-slate-200 text-slate-500 hover:text-slate-900">
                                            <Bookmark className="h-3.5 w-3.5" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Featured Image */}
                        {(article.featured_image || article.service?.cover_image) && (
                            <div className="w-full aspect-[21/9] rounded-2xl overflow-hidden mb-12 shadow-md border border-slate-100">
                                <img
                                    src={article.featured_image ? (article.featured_image.startsWith('http') ? article.featured_image : `/storage/${article.featured_image}`) : article.service?.cover_image}
                                    alt={article.title}
                                    className="w-full h-full object-cover object-center"
                                />
                            </div>
                        )}

                        {/* Article Content Area */}
                        <div className="bg-white/80 backdrop-blur-md rounded-2xl p-6 sm:p-10 md:p-12 shadow-sm border border-slate-100/80 mb-12 relative">
                            {/* Content grid layer */}
                            <div className="absolute inset-0 bg-[linear-gradient(to_right,#e2e8f030_1px,transparent_1px),linear-gradient(to_bottom,#e2e8f030_1px,transparent_1px)] bg-[size:3rem_3rem] pointer-events-none rounded-2xl" />

                            <div 
                                className="relative z-10 prose prose-slate max-w-none text-slate-700 leading-relaxed font-light
                                    prose-headings:font-bold prose-headings:text-slate-900 prose-headings:tracking-tight
                                    prose-h2:text-2xl prose-h2:mt-8 prose-h2:mb-4
                                    prose-p:mb-6 prose-p:text-lg
                                    prose-a:text-indigo-650 prose-a:underline hover:prose-a:text-indigo-800
                                    prose-strong:font-semibold prose-strong:text-slate-900
                                    prose-ul:list-disc prose-ul:ps-6 prose-ul:mb-6
                                    prose-ol:list-decimal prose-ol:ps-6 prose-ol:mb-6
                                    prose-blockquote:border-s-4 prose-blockquote:border-indigo-500 prose-blockquote:ps-4 prose-blockquote:italic prose-blockquote:text-slate-600 prose-blockquote:my-8"
                                dangerouslySetInnerHTML={{ __html: cleanHtml }}
                            />
                        </div>

                        {/* Related Service Section */}
                        {article.service && (
                            <motion.div
                                initial={{ opacity: 0, y: 20 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ duration: 0.5, delay: 0.2 }}
                                className="bg-gradient-to-br from-indigo-950 to-slate-950 text-white rounded-3xl p-6 sm:p-8 shadow-xl border border-indigo-500/25 mb-12 overflow-hidden relative"
                            >
                                <div className="absolute top-0 right-0 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none" />
                                <div className="absolute bottom-0 left-0 w-48 h-48 bg-purple-500/10 rounded-full blur-2xl pointer-events-none" />
                                
                                <div className="relative z-10 flex flex-col md:flex-row items-center gap-6 sm:gap-8">
                                    {/* Service Image */}
                                    <div className="w-full md:w-1/3 aspect-[16/10] rounded-2xl overflow-hidden bg-slate-900 border border-slate-800 shadow-md shrink-0">
                                        <img 
                                            src={article.service.cover_image} 
                                            alt={article.service.title}
                                            className="w-full h-full object-cover"
                                        />
                                    </div>

                                    {/* Service Info */}
                                    <div className="flex-grow text-center md:text-start space-y-3">
                                        <Badge className="bg-indigo-500/20 text-indigo-300 border-none shadow-sm uppercase font-bold tracking-widest text-xs px-2.5 py-1">
                                            {__('general.related_service') || 'Related Service'}
                                        </Badge>
                                        <h3 className="text-xl sm:text-2xl font-extrabold text-white leading-snug tracking-tight">
                                            {article.service.title}
                                        </h3>
                                        <p className="text-slate-300 text-sm font-light max-w-xl leading-relaxed">
                                            {article.service.seller?.name ? `${__('general.by')} ${article.service.seller.name}` : ''}
                                        </p>
                                    </div>

                                    {/* Price & CTA */}
                                    <div className="w-full md:w-auto shrink-0 flex flex-col items-center md:items-end gap-3.5 pt-4 md:pt-0 md:ps-6 border-t md:border-t-0 md:border-s border-white/10">
                                        <div className="text-center md:text-end">
                                            <span className="block text-xs uppercase tracking-widest text-slate-400 font-medium mb-1">
                                                {__('general.starting_from') || 'Starting Price'}
                                            </span>
                                            <span className="text-2xl sm:text-3xl font-black text-indigo-300">
                                                {(() => {
                                                    const pkgs = article.service.packages;
                                                    if (article.service.is_free) return __('general.free') || 'Free';
                                                    if (!pkgs || pkgs.length === 0) return '$5';
                                                    const cheapest = pkgs.reduce((min, p) => Number(p.price) < Number(min.price) ? p : min, pkgs[0]);
                                                    return formatCurrency(cheapest.price, cheapest.currency || 'USD');
                                                })()}
                                            </span>
                                        </div>
                                        <a 
                                            href={article.service.url || `/marketplace/services/${article.service.id}-${article.service.slug}`}
                                            className="w-full md:w-auto"
                                        >
                                            <Button className="w-full md:w-auto px-6 py-5 rounded-xl bg-white hover:bg-slate-100 text-slate-900 font-bold shadow-md hover:shadow-lg transition-all duration-300 flex items-center justify-center gap-2 group border-none">
                                                <span>{__('general.order_now') || 'Order Now'}</span>
                                                <ArrowRight className="h-4 w-4 group-hover:translate-x-1 transition-transform" />
                                            </Button>
                                        </a>
                                    </div>
                                </div>
                            </motion.div>
                        )}
                    </motion.article>
                </div>
            </div>
        </PublicLayout>
    );
}


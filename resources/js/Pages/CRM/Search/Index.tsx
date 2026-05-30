import React, { useState, useEffect } from 'react';
import CrmLayout from '@/Layouts/CrmLayout';
import { __ } from '@/lib/i18n';
import { Input } from '@/Components/ui/input';
import { Search as SearchIcon, Loader2, Users, ArrowRight } from 'lucide-react';
import axios from 'axios';
import { router } from '@inertiajs/react';
import { Card, CardContent } from '@/Components/ui/card';

export default function SearchIndex() {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<any[]>([]);
    const [loading, setLoading] = useState(false);
    const [hasSearched, setHasSearched] = useState(false);

    useEffect(() => {
        if (!query || query.length < 2) {
            setResults([]);
            setHasSearched(false);
            return;
        }

        const delayDebounceFn = setTimeout(async () => {
            setLoading(true);
            setHasSearched(true);
            try {
                const res = await axios.get(route('crm.search', { q: query }));
                setResults(res.data);
            } catch (error) {
                console.error("Search failed", error);
            } finally {
                setLoading(false);
            }
        }, 400);

        return () => clearTimeout(delayDebounceFn);
    }, [query]);

    return (
        <CrmLayout title={__('Universal Search')} activeMenu="search">
            <div className="flex flex-col h-full items-center p-8 pt-16">
                
                <div className="w-full max-w-3xl space-y-8">
                    {/* Search Header */}
                    <div className="text-center space-y-4">
                        <div className="mx-auto w-16 h-16 bg-indigo-100 text-indigo-600 rounded-2xl flex items-center justify-center mb-6 shadow-sm">
                            <SearchIcon size={32} strokeWidth={2.5} />
                        </div>
                        <h1 className="text-4xl font-extrabold tracking-tight text-slate-900">{__('What are you looking for?')}</h1>
                        <p className="text-lg text-slate-500">{__('Search across all your leads, contacts, and campaigns instantly.')}</p>
                    </div>

                    {/* Search Input */}
                    <div className="relative group">
                        <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            {loading ? (
                                <Loader2 className="h-6 w-6 text-indigo-500 animate-spin" />
                            ) : (
                                <SearchIcon className="h-6 w-6 text-slate-400 group-focus-within:text-indigo-500 transition-colors" />
                            )}
                        </div>
                        <Input
                            type="text"
                            className="block w-full pl-12 pr-4 py-6 text-lg border-slate-200 bg-white rounded-2xl shadow-sm focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                            placeholder={__('Type a name, email, or phone number...')}
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            autoFocus
                        />
                    </div>

                    {/* Results Area */}
                    {query.length >= 2 && (
                        <div className="mt-8 animate-in fade-in slide-in-from-bottom-4 duration-300">
                            <h3 className="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-4">
                                {__('Search Results')}
                            </h3>
                            
                            {results.length > 0 ? (
                                <div className="space-y-3">
                                    {results.map((item) => (
                                        <Card key={item.id} className="overflow-hidden border border-slate-200 hover:border-indigo-300 hover:shadow-md transition-all cursor-pointer group" onClick={() => router.visit(item.url)}>
                                            <CardContent className="p-4 flex items-center justify-between">
                                                <div className="flex items-center gap-4">
                                                    <div className="w-10 h-10 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-colors">
                                                        {item.type === 'Lead' && <Users size={20} />}
                                                    </div>
                                                    <div>
                                                        <h4 className="font-semibold text-slate-900 text-base">{item.title}</h4>
                                                        <p className="text-sm text-slate-500">{item.subtitle}</p>
                                                    </div>
                                                </div>
                                                <div className="text-slate-400 group-hover:text-indigo-600 transition-colors">
                                                    <ArrowRight size={20} />
                                                </div>
                                            </CardContent>
                                        </Card>
                                    ))}
                                </div>
                            ) : !loading && hasSearched && (
                                <div className="text-center py-12 bg-slate-50 rounded-2xl border border-slate-100 border-dashed">
                                    <p className="text-slate-500 font-medium">{__('No results found for')} "{query}"</p>
                                    <p className="text-sm text-slate-400 mt-1">{__('Try adjusting your search terms.')}</p>
                                </div>
                            )}
                        </div>
                    )}
                </div>

            </div>
        </CrmLayout>
    );
}

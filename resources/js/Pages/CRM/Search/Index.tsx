import React, { useState, useEffect } from 'react';
import CrmLayout from '@/Layouts/CrmLayout';
import { __ } from '@/lib/i18n';
import { Input } from '@/Components/ui/input';
import { Search as SearchIcon, Loader2, Users, ArrowRight } from 'lucide-react';
import axios from 'axios';
import { router, usePage } from '@inertiajs/react';
import { Card, CardContent } from '@/Components/ui/card';
import { UpgradeOverlay } from '@/Components/ui/UpgradeOverlay';

export default function SearchIndex() {
    const { auth } = usePage().props;
    const hasSalesStaff = (auth as any)?.crm_features?.includes('crm-sales-staff') ?? false;

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
            } catch (error: any) {
                console.error("Search failed", error);
            } finally {
                setLoading(false);
            }
        }, 400);

        return () => clearTimeout(delayDebounceFn);
    }, [query]);

    if (!hasSalesStaff) {
        return (
            <CrmLayout title={__('general.universal_search')} activeMenu="search">
                <div className="p-8">
                    <UpgradeOverlay 
                        title={__('general.sales_staff_add_on_required')}
                        description={__('general.to_use_universal_search_across_leads_contacts_and_campaigns_you_need_the_sales_staff_operations_add_on')}
                        icon={SearchIcon}
                        module="crm-sales-staff"
                        priceText={__('general.subscribe_to_sales_staff')}
                    />
                </div>
            </CrmLayout>
        );
    }

    return (
        <CrmLayout title={__('general.universal_search')} activeMenu="search">
            <div className="flex flex-col h-full items-center p-8 pt-16">
                
                <div className="w-full max-w-3xl space-y-8">
                    {/* Search Header */}
                    <div className="text-center space-y-4">
                        <div className="mx-auto w-16 h-16 bg-indigo-100 text-indigo-600 rounded-2xl flex items-center justify-center mb-6 shadow-sm">
                            <SearchIcon size={32} strokeWidth={2.5} />
                        </div>
                        <h1 className="text-4xl font-extrabold tracking-tight text-slate-900">{__('general.what_are_you_looking_for')}</h1>
                        <p className="text-lg text-slate-500">{__('general.search_across_all_your_leads_contacts_and_campaigns_instantly')}</p>
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
                            placeholder={__('general.type_a_name_email_or_phone_number')}
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            autoFocus
                        />
                    </div>

                    {/* Results Area */}
                    {query.length >= 2 && (
                        <div className="mt-8 animate-in fade-in slide-in-from-bottom-4 duration-300">
                            <h3 className="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-4">
                                {__('general.search_results')}
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
                                    <p className="text-slate-500 font-medium">{__('general.no_results_found_for')} "{query}"</p>
                                    <p className="text-sm text-slate-400 mt-1">{__('general.try_adjusting_your_search_terms')}</p>
                                </div>
                            )}
                        </div>
                    )}
                </div>

            </div>
        </CrmLayout>
    );
}

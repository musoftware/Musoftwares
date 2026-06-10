import React, { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import PublicLayout from '../PublicLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Search, SlidersHorizontal, MapPin, Star, User, ChevronRight } from 'lucide-react';
import { PageHeader } from '@/Components/ui/PageHeader';
import { EmptyState } from '@/Components/ui/EmptyState';
import { cn } from '@/lib/utils';
import { __ } from '@/lib/i18n';
import { SeoHead } from '@/Components/ui/SeoHead';
import { FreelanceCard } from '@/Components/Freelance/ui/FreelanceCard';
import { formatMoney } from '@/lib/utils';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';

function BrowseFreelancersContent({ freelancers, userCurrency }: any) {
    const urlParams = new URLSearchParams(typeof window !== 'undefined' ? window.location.search : '');
    const [searchTerm, setSearchTerm] = useState(urlParams.get('search') || '');
    const [sortBy, setSortBy] = useState(urlParams.get('sort') || 'newest');
    const [rateMin, setRateMin] = useState(urlParams.get('rate_min') || '');
    const [rateMax, setRateMax] = useState(urlParams.get('rate_max') || '');

    const sortLabels: Record<string, string> = {
        newest: __('general.newest_first'),
        rate_high: __('erp.highest_rate', undefined, 'Highest Rate'),
        rate_low: __('erp.lowest_rate', undefined, 'Lowest Rate')
    };

    const displayFreelancers = freelancers?.data?.length ? freelancers.data : [];

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        updateFilters(sortBy, rateMin, rateMax);
    };

    const updateFilters = (newSort: string, minRate?: string, maxRate?: string) => {
        router.get('/freelance/freelancers/browse', {
            search: searchTerm || undefined,
            sort: newSort !== 'newest' ? newSort : undefined,
            rate_min: minRate !== undefined ? minRate : (rateMin || undefined),
            rate_max: maxRate !== undefined ? maxRate : (rateMax || undefined)
        }, { preserveState: true });
    };

    const handleSortChange = (val: string | null) => {
        const newSort = val || 'newest';
        setSortBy(newSort);
        updateFilters(newSort, rateMin, rateMax);
    };

    return (
        <>
            <SeoHead 
                title={`${__('freelance.nav.browse_freelancers', undefined, 'Find Talent')} - ${__('freelance.freelance')}`} 
                description={__('freelance.seo.browse_freelancers_desc', undefined, 'Discover top freelance talent for your next project.')}
            />

            <PageHeader
                title={__('freelance.nav.browse_freelancers', undefined, 'Find Talent')}
                subtitle={__('freelance.browse_freelancers_subtitle', undefined, 'Hire the best professionals to get the job done.')}
                icon={User}
            />

            <form 
                onSubmit={handleSearch} 
                className="flex flex-col sm:flex-row items-stretch sm:items-center bg-white border border-slate-200 rounded-xl shadow-sm hover:border-slate-300 transition-all duration-200 p-1.5 gap-2 sm:gap-0 focus-within:ring-4 focus-within:ring-indigo-500/10 focus-within:border-indigo-500 w-full mb-6"
            >
                <div className="relative flex-1 flex items-center min-w-0 pl-3">
                    <Search className="h-5 w-5 text-slate-400 shrink-0" />
                    <input 
                        type="text"
                        placeholder={__('freelance.search_freelancers', undefined, 'Search by name, title, or skills...')} 
                        className="w-full bg-transparent border-0 ring-0 focus:ring-0 focus:outline-none pl-3 text-sm text-slate-900 placeholder:text-slate-400 h-11"
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                    />
                </div>
                
                <div className="hidden sm:block w-px h-6 bg-slate-200 mx-2 shrink-0" />

                <Button 
                    type="submit" 
                    className="h-11 rounded-lg px-6 text-sm font-semibold bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm shrink-0"
                >
                    {__('general.search')}
                </Button>
            </form>

            <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
                {/* Filters Sidebar */}
                <div className="space-y-6">
                    <FreelanceCard className="p-5">
                        <h3 className="text-sm font-semibold text-slate-900 mb-4 flex items-center gap-2">
                            <SlidersHorizontal className="h-4 w-4" /> {__('general.filters')}
                        </h3>
                        <div className="space-y-5">
                            <div className="space-y-3">
                                <label className="text-xs font-semibold uppercase tracking-wider text-slate-500">{__('freelance.hourly_rate', undefined, 'Hourly Rate')}</label>
                                <div className="flex items-center gap-2">
                                    <Input 
                                        type="number" 
                                        placeholder={__('erp.min')} 
                                        className="h-9 text-sm" 
                                        value={rateMin}
                                        onChange={(e) => setRateMin(e.target.value)}
                                        onBlur={(e) => updateFilters(sortBy, e.target.value, rateMax)}
                                    />
                                    <span className="text-slate-400">-</span>
                                    <Input 
                                        type="number" 
                                        placeholder={__('erp.max')} 
                                        className="h-9 text-sm" 
                                        value={rateMax}
                                        onChange={(e) => setRateMax(e.target.value)}
                                        onBlur={(e) => updateFilters(sortBy, rateMin, e.target.value)}
                                    />
                                </div>
                            </div>
                            <div className="w-full h-px bg-slate-100"></div>
                            <div className="space-y-3">
                                <label className="text-xs font-semibold uppercase tracking-wider text-slate-500">{__('general.experience_level')}</label>
                                <div className="space-y-2.5">
                                    {['Entry Level', 'Intermediate', 'Expert'].map(level => (
                                        <label key={level} className="flex items-center gap-2.5 cursor-pointer group">
                                            <input type="checkbox" className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4" />
                                            <span className="text-sm text-slate-700 group-hover:text-slate-900">{__(level)}</span>
                                        </label>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </FreelanceCard>
                </div>

                {/* Freelancer Listings */}
                <div className="lg:col-span-3 space-y-4">
                    <div className="flex items-center justify-between mb-2">
                        <h2 className="text-sm font-semibold text-slate-900">
                            {freelancers?.total || 0} {__('freelance.freelancers_found', undefined, 'Freelancers found')}
                        </h2>
                        <Select value={sortBy} onValueChange={handleSortChange}>
                            <SelectTrigger className="w-[180px] text-sm shadow-sm border-slate-200" style={{ height: '2.25rem' }}>
                                <SelectValue placeholder={__('general.sort_by')}>
                                    {sortLabels[sortBy] || __('general.sort_by')}
                                </SelectValue>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="newest">{__('general.newest_first')}</SelectItem>
                                <SelectItem value="rate_high">{__('erp.highest_rate', undefined, 'Highest Rate')}</SelectItem>
                                <SelectItem value="rate_low">{__('erp.lowest_rate', undefined, 'Lowest Rate')}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    {!displayFreelancers.length ? (
                        <FreelanceCard>
                            <EmptyState
                                icon={Search}
                                title={__('freelance.no_freelancers_found', undefined, 'No freelancers found')}
                                description={__('general.try_adjusting_your_search_or')}
                                actionLabel={__('general.clear_filters')}
                                actionIcon={Search}
                                onClick={() => router.get('/freelance/freelancers/browse')}
                            />
                        </FreelanceCard>
                    ) : (
                        displayFreelancers.map((freelancer: any) => (
                            <FreelanceCard key={freelancer.id} className="p-6">
                                <div className="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                                    <div className="flex gap-4 flex-1">
                                        <Avatar className="h-16 w-16 border border-slate-200 rounded-xl shrink-0">
                                            <AvatarImage src={freelancer.avatar_url || ''} alt={freelancer.name} />
                                            <AvatarFallback className="bg-indigo-50 text-indigo-700 font-bold rounded-xl text-lg">
                                                {freelancer.name?.substring(0, 2).toUpperCase()}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div className="space-y-2 flex-1">
                                            <div>
                                                <h3 className="text-lg font-semibold text-slate-900 leading-tight">
                                                    {freelancer.name}
                                                </h3>
                                                <p className="text-sm font-medium text-slate-600">
                                                    {freelancer.freelance_profile?.title || __('freelance.freelancer')}
                                                </p>
                                            </div>
                                            
                                            <div className="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-slate-600">
                                                <span className="flex items-center gap-1 font-semibold text-slate-900">
                                                    {freelancer.freelance_profile?.hourly_rate !== null ? 
                                                        `${formatMoney(freelancer.freelance_profile?.hourly_rate, userCurrency || freelancer.currency)} / ${__('general.hr')}` : 
                                                        __('freelance.rate_negotiable', undefined, 'Rate Negotiable')
                                                    }
                                                </span>
                                                {freelancer.freelance_profile?.average_rating > 0 && (
                                                    <span className="flex items-center gap-1">
                                                        <Star className="h-4 w-4 text-amber-500 fill-amber-500" />
                                                        <span className="font-semibold text-slate-900">{Number(freelancer.freelance_profile.average_rating).toFixed(1)}</span>
                                                        <span className="text-slate-500">({freelancer.freelance_profile.reviews_count} {__('freelance.reviews', undefined, 'reviews')})</span>
                                                    </span>
                                                )}
                                            </div>

                                            <p className="text-sm text-slate-600 line-clamp-2 leading-relaxed mt-2">
                                                {freelancer.freelance_profile?.bio || __('freelance.no_bio_provided', undefined, 'No bio provided.')}
                                            </p>

                                            <div className="flex flex-wrap gap-1.5 pt-2">
                                                {freelancer.freelance_skills?.map((skill: any) => (
                                                    <Badge key={skill.id} variant="outline" className="bg-slate-50 border-slate-200 text-slate-600 font-medium text-[11px] rounded-md px-2">
                                                        {skill.name}
                                                    </Badge>
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </FreelanceCard>
                        ))
                    )}
                    
                    {/* Pagination */}
                    {freelancers?.links && freelancers.data?.length > 0 && (
                        <div className="flex justify-center gap-2 pt-8">
                            {freelancers.links.map((link: any, i: number) => (
                                link.url ? (
                                    <Link
                                        key={i}
                                        href={link.url}
                                        className={cn(
                                            'px-3 py-1.5 rounded-lg text-xs font-semibold border transition-colors',
                                            link.active
                                                ? 'bg-indigo-600 text-white border-indigo-600'
                                                : 'bg-white text-slate-600 border-slate-200 hover:border-indigo-300'
                                        )}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ) : (
                                    <span
                                        key={i}
                                        className="px-3 py-1.5 rounded-lg text-xs font-semibold border border-slate-100 bg-slate-50 text-slate-400 cursor-not-allowed"
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                )
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

export default function BrowseFreelancers({ freelancers, userCurrency }: any) {
    return (
        <PublicLayout>
            <BrowseFreelancersContent freelancers={freelancers} userCurrency={userCurrency} />
        </PublicLayout>
    );
}

import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';
import { Search, MapPin, Briefcase, Calendar, MessageSquare, ArrowRight, DollarSign } from 'lucide-react';

interface Listing {
    id: number;
    title: string;
    description: string;
    price: number;
    currency: string;
    city: string;
    images: string[];
    created_at: string;
}

interface IndexProps {
    listings: {
        data: Listing[];
        current_page: number;
        last_page: number;
        links: any[];
    };
    cities: string[];
    filters: {
        q?: string;
        city?: string;
    };
}

export default function Index({ listings, cities, filters }: IndexProps) {
    const [search, setSearch] = useState(filters.q || '');
    const [selectedCity, setSelectedCity] = useState(filters.city || '');

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/listing', { q: search, city: selectedCity }, { preserveState: true });
    };

    const handleCityChange = (city: string) => {
        setSelectedCity(city);
        router.get('/listing', { q: search, city: city }, { preserveState: true });
    };

    return (
        <PublicLayout>
            <Head>
                <title>بوابة الوظائف الشاغرة | Musoftwares Jobs</title>
                <meta name="description" content="تصفح وابحث عن أحدث الوظائف الشاغرة وفرص العمل المعلنة في مصر. تواصل مباشرة مع أصحاب الأعمال." />
            </Head>

            <div className="min-h-screen bg-slate-950 text-slate-100 py-12 px-4 sm:px-6 lg:px-8">
                {/* Hero Section */}
                <div className="max-w-6xl mx-auto text-center mb-12">
                    <h1 className="text-4xl sm:text-5xl font-extrabold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-blue-400 via-indigo-400 to-purple-400 mb-4 font-sans">
                        ابحث عن وظيفتك القادمة
                    </h1>
                    <p className="text-lg text-slate-400 max-w-2xl mx-auto">
                        منصة Musoftwares تجمع لك أحدث الوظائف الشاغرة من كبرى شركات التوظيف مباشرة وبدون وسيط.
                    </p>
                </div>

                {/* Filter and Search Bar */}
                <div className="max-w-5xl mx-auto mb-10">
                    <form onSubmit={handleSearch} className="bg-slate-900/60 border border-slate-800 p-4 rounded-2xl backdrop-blur-xl shadow-xl flex flex-col md:flex-row gap-4 items-center">
                        <div className="relative w-full md:flex-1">
                            <Search className="absolute right-4 top-3.5 h-5 w-5 text-slate-500" />
                            <input
                                type="text"
                                placeholder="ابحث عن مسمى وظيفي أو مهارة..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="w-full bg-slate-950 border border-slate-800 rounded-xl pr-12 pl-4 py-3 text-slate-100 placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-right transition-colors"
                            />
                        </div>

                        <div className="w-full md:w-64">
                            <select
                                value={selectedCity}
                                onChange={(e) => handleCityChange(e.target.value)}
                                className="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-3 text-slate-100 focus:outline-none focus:border-blue-500 text-right transition-colors"
                            >
                                <option value="">كل المدن</option>
                                {cities.map((city) => (
                                    <option key={city} value={city}>{city}</option>
                                ))}
                            </select>
                        </div>

                        <button
                            type="submit"
                            className="w-full md:w-auto px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-medium rounded-xl shadow-lg hover:shadow-indigo-500/20 transition-all duration-300"
                        >
                            بحث
                        </button>
                    </form>
                </div>

                {/* Listings Grid */}
                <div className="max-w-5xl mx-auto">
                    {listings.data.length === 0 ? (
                        <div className="text-center py-16 bg-slate-900/40 border border-slate-800 rounded-2xl">
                            <Briefcase className="h-16 w-16 mx-auto text-slate-600 mb-4" />
                            <h3 className="text-xl font-bold text-slate-300">لا توجد وظائف مطابقة للبحث</h3>
                            <p className="text-slate-500 mt-2">يرجى تجربة كلمات بحث أخرى أو تغيير تصفية المدن.</p>
                        </div>
                    ) : (
                        <div className="grid gap-6">
                            {listings.data.map((listing) => (
                                <div
                                    key={listing.id}
                                    className="group relative bg-slate-900/50 hover:bg-slate-900/80 border border-slate-800/80 hover:border-slate-700/80 rounded-2xl p-6 transition-all duration-300 flex flex-col md:flex-row gap-6 shadow-md hover:shadow-xl hover:translate-y-[-2px]"
                                >
                                    {/* Company Icon or First Scraped Image */}
                                    <div className="w-20 h-20 md:w-24 md:h-24 bg-gradient-to-br from-slate-800 to-slate-950 border border-slate-800 rounded-xl flex items-center justify-center shrink-0 overflow-hidden">
                                        {listing.images && listing.images.length > 0 ? (
                                            <img
                                                src={listing.images[0]}
                                                alt={listing.title}
                                                className="w-full h-full object-cover object-center"
                                                loading="lazy"
                                            />
                                        ) : (
                                            <Briefcase className="h-10 w-10 text-indigo-400 group-hover:scale-110 transition-transform duration-300" />
                                        )}
                                    </div>

                                    {/* Listing Content */}
                                    <div className="flex-1 flex flex-col justify-between">
                                        <div>
                                            <div className="flex flex-wrap items-center justify-between gap-2 mb-2">
                                                <h2 className="text-xl font-bold text-slate-100 group-hover:text-blue-400 transition-colors">
                                                    <Link href={`/listing/${listing.id}`}>
                                                        {listing.title}
                                                    </Link>
                                                </h2>
                                                
                                                <span className="text-sm font-semibold text-emerald-400 bg-emerald-950/40 border border-emerald-900/50 px-3 py-1 rounded-full flex items-center gap-1">
                                                    <DollarSign className="h-4 w-4" />
                                                    {listing.price > 0 ? `${listing.price} ${listing.currency}` : 'قابل للتفاوض / غير محدد'}
                                                </span>
                                            </div>

                                            <p className="text-slate-400 text-sm line-clamp-3 leading-relaxed mb-4">
                                                {listing.description}
                                            </p>
                                        </div>

                                        {/* Metadata */}
                                        <div className="flex flex-wrap items-center justify-between gap-4 border-t border-slate-850 pt-4 text-xs text-slate-500">
                                            <div className="flex items-center gap-4">
                                                {listing.city && (
                                                    <span className="flex items-center gap-1">
                                                        <MapPin className="h-4 w-4 text-slate-400" />
                                                        {listing.city}
                                                    </span>
                                                )}
                                                <span className="flex items-center gap-1">
                                                    <Calendar className="h-4 w-4 text-slate-400" />
                                                    نُشر {listing.created_at}
                                                </span>
                                            </div>

                                            <Link
                                                href={`/listing/${listing.id}`}
                                                className="flex items-center gap-1 text-sm font-semibold text-blue-400 hover:text-blue-300 transition-colors"
                                            >
                                                تفاصيل الوظيفة
                                                <ArrowRight className="h-4 w-4 rtl:rotate-180" />
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* Pagination */}
                {listings.last_page > 1 && (
                    <div className="max-w-5xl mx-auto mt-12 flex justify-center gap-2">
                        {listings.links.map((link, idx) => {
                            if (link.url === null) return null;
                            const isActive = link.active;
                            return (
                                <Link
                                    key={idx}
                                    href={link.url}
                                    className={`px-4 py-2 text-sm font-medium rounded-lg border transition-all duration-200 ${
                                        isActive
                                            ? 'bg-blue-600 border-blue-600 text-white shadow-lg shadow-blue-500/20'
                                            : 'bg-slate-900 border-slate-800 text-slate-400 hover:bg-slate-850 hover:text-slate-100'
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            );
                        })}
                    </div>
                )}
            </div>
        </PublicLayout>
    );
}

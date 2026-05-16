import React from 'react';
import { Head, Link } from '@inertiajs/react';
import MarketplaceLayout from '@/Layouts/MarketplaceLayout';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { ScrollArea, ScrollBar } from '@/components/ui/scroll-area';
import { Search, Star, Heart } from 'lucide-react';

export default function Browse() {
    const categories = [
        'All', 'Development', 'Design', 'Marketing', 'Writing', 'Video', 'Audio', 'Business'
    ];

    const services = [
        {
            id: 1,
            title: 'Logo Design for Startups',
            seller: '@sara_design',
            rating: 4.9,
            reviews: 128,
            price: 25,
            image: 'https://placehold.co/600x400/png',
            featured: true
        },
        {
            id: 2,
            title: 'Full Stack Web Development',
            seller: '@dev_john',
            rating: 5.0,
            reviews: 84,
            price: 150,
            image: 'https://placehold.co/600x400/png',
            featured: false
        },
        {
            id: 3,
            title: 'SEO Optimization & Marketing',
            seller: '@marketing_pro',
            rating: 4.8,
            reviews: 215,
            price: 50,
            image: 'https://placehold.co/600x400/png',
            featured: false
        },
        {
            id: 4,
            title: 'Professional Copywriting',
            seller: '@wordsmith',
            rating: 4.7,
            reviews: 92,
            price: 30,
            image: 'https://placehold.co/600x400/png',
            featured: true
        },
        {
            id: 5,
            title: 'Video Editing for YouTube',
            seller: '@video_wizard',
            rating: 4.9,
            reviews: 156,
            price: 45,
            image: 'https://placehold.co/600x400/png',
            featured: false
        },
        {
            id: 6,
            title: 'Mobile App UI/UX Design',
            seller: '@ui_expert',
            rating: 5.0,
            reviews: 73,
            price: 80,
            image: 'https://placehold.co/600x400/png',
            featured: false
        }
    ];

    return (
        <MarketplaceLayout>
            <Head title="Browse Services" />

            {/* Hero Banner */}
            <div className="bg-indigo-900 text-white py-16 px-4 sm:px-6 lg:px-8 text-center">
                <h1 className="text-4xl font-extrabold tracking-tight sm:text-5xl lg:text-6xl mb-6">
                    Find the perfect service
                </h1>
                <div className="max-w-2xl mx-auto flex">
                    <Input
                        type="text"
                        placeholder="What are you looking for?"
                        className="h-14 text-lg rounded-r-none text-black bg-white focus-visible:ring-0 focus-visible:ring-offset-0 border-0"
                    />
                    <Button className="h-14 px-8 rounded-l-none bg-green-600 hover:bg-green-700 text-lg">
                        <Search className="mr-2 h-5 w-5" />
                        Search
                    </Button>
                </div>
                <div className="mt-6 flex flex-wrap justify-center gap-2">
                    <span className="text-sm font-medium opacity-80 mr-2 flex items-center">Popular:</span>
                    {['Website Design', 'WordPress', 'Logo Design', 'Dropshipping'].map(pill => (
                        <span key={pill} className="px-3 py-1 rounded-full border border-white/30 text-sm hover:bg-white/10 cursor-pointer transition-colors">
                            {pill}
                        </span>
                    ))}
                </div>
            </div>

            {/* Main Content */}
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

                {/* Category Filter */}
                <div className="mb-8">
                    <ScrollArea className="w-full whitespace-nowrap rounded-md border bg-white shadow-sm p-1">
                        <div className="flex w-max space-x-2 p-2">
                            {categories.map((category, idx) => (
                                <Button
                                    key={category}
                                    variant={idx === 0 ? "default" : "ghost"}
                                    className={idx === 0 ? "bg-indigo-600 hover:bg-indigo-700" : ""}
                                >
                                    {category}
                                </Button>
                            ))}
                        </div>
                        <ScrollBar orientation="horizontal" />
                    </ScrollArea>
                </div>

                {/* Services Grid */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    {services.map(service => (
                        <Link href={route('marketplace.services.show', service.id)} key={service.id} className="block group">
                            <Card className={`overflow-hidden transition-all duration-300 hover:shadow-xl hover:-translate-y-1 h-full flex flex-col ${service.featured ? 'border-amber-400 border-2' : ''}`}>
                                <div className="relative aspect-[3/2] overflow-hidden bg-gray-100">
                                    <img
                                        src={service.image}
                                        alt={service.title}
                                        className="object-cover w-full h-full transition-transform duration-300 group-hover:scale-105"
                                    />
                                    <button className="absolute top-3 right-3 p-2 rounded-full bg-white/80 text-gray-500 hover:text-red-500 hover:bg-white transition-colors opacity-0 group-hover:opacity-100 shadow-sm">
                                        <Heart className="h-5 w-5" />
                                    </button>
                                    {service.featured && (
                                        <div className="absolute top-3 left-3 px-2 py-1 bg-amber-400 text-amber-900 text-xs font-bold rounded-md shadow-sm">
                                            FEATURED
                                        </div>
                                    )}
                                </div>
                                <CardContent className="p-4 flex-1 flex flex-col">
                                    <div className="flex items-center space-x-2 mb-2">
                                        <div className="flex items-center text-amber-500 font-semibold text-sm">
                                            <Star className="h-4 w-4 fill-current mr-1" />
                                            {service.rating}
                                        </div>
                                        <span className="text-gray-500 text-sm">({service.reviews})</span>
                                    </div>

                                    <h3 className="font-semibold text-gray-900 text-lg mb-1 line-clamp-2 group-hover:text-indigo-600 transition-colors">
                                        {service.title}
                                    </h3>
                                    <p className="text-sm text-gray-500 mb-4">
                                        by {service.seller}
                                    </p>

                                    <div className="mt-auto pt-4 border-t border-gray-100 flex items-center justify-between">
                                        <span className="text-xs font-medium text-gray-500 uppercase tracking-wider">Starting at</span>
                                        <span className="text-xl font-bold text-gray-900">${service.price}</span>
                                    </div>

                                    {/* Quick View Button (Visible on hover) */}
                                    <div className="absolute inset-x-0 bottom-0 p-4 bg-white translate-y-full group-hover:translate-y-0 transition-transform duration-300 border-t flex justify-center opacity-0 group-hover:opacity-100 shadow-[0_-10px_15px_-3px_rgba(0,0,0,0.1)]">
                                        <Button className="w-full bg-indigo-600 hover:bg-indigo-700">Quick View</Button>
                                    </div>
                                </CardContent>
                            </Card>
                        </Link>
                    ))}
                </div>
            </div>
        </MarketplaceLayout>
    );
}

import React from 'react';
import { Head, Link } from '@inertiajs/react';
import MarketplaceLayout from '@/Layouts/MarketplaceLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { ChevronRight, Star, Clock, RefreshCw, Check } from 'lucide-react';

export default function Show() {
    const service = {
        title: 'Logo Design for Startups',
        seller: {
            name: 'Sara Design',
            handle: '@sara_design',
            rating: 4.9,
            reviews: 128,
            memberSince: 'Oct 2021',
            avatar: '/avatars/02.png'
        },
        images: [
            'https://placehold.co/800x500/png',
            'https://placehold.co/200x150/png',
            'https://placehold.co/200x150/png',
            'https://placehold.co/200x150/png',
        ],
        description: `
            <p>I will design a unique, modern, and professional logo for your startup or business.</p>
            <br/>
            <p><strong>What you get:</strong></p>
            <ul>
                <li>High-resolution files (PNG, JPG)</li>
                <li>Vector source files (AI, EPS, SVG)</li>
                <li>Multiple concepts to choose from</li>
                <li>Unlimited revisions until you are 100% satisfied</li>
            </ul>
            <br/>
            <p>My design process is collaborative and focused on understanding your brand's core values.</p>
        `,
        packages: {
            basic: { name: 'Basic', price: 25, deliveryDays: 3, revisions: 1, features: ['1 Logo Concept', 'High Res JPG/PNG', '3 days delivery'] },
            standard: { name: 'Standard', price: 50, deliveryDays: 5, revisions: 3, features: ['3 Logo Concepts', 'High Res JPG/PNG', 'Vector Files', '5 days delivery'] },
            premium: { name: 'Pro Package', price: 99, deliveryDays: 7, revisions: 'Unlimited', features: ['5 Logo Concepts', 'High Res JPG/PNG', 'Vector Files', 'Social Media Kit', 'Unlimited Revisions', '7 days delivery'] }
        }
    };

    const userBalance = 125.00;
    const selectedPackage = service.packages.premium;
    const hasInsufficientBalance = userBalance < selectedPackage.price;

    return (
        <MarketplaceLayout>
            <Head title={service.title} />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Breadcrumbs */}
                <nav className="flex text-sm text-gray-500 mb-6">
                    <ol className="flex items-center space-x-2">
                        <li><Link href={route('marketplace.services.index')} className="hover:text-indigo-600">Marketplace</Link></li>
                        <li><ChevronRight className="h-4 w-4" /></li>
                        <li><Link href="#" className="hover:text-indigo-600">Design</Link></li>
                        <li><ChevronRight className="h-4 w-4" /></li>
                        <li className="text-gray-900 font-medium truncate max-w-[200px]">{service.title}</li>
                    </ol>
                </nav>

                <h1 className="text-3xl font-bold text-gray-900 mb-8">{service.title}</h1>

                <div className="flex flex-col lg:flex-row gap-8">
                    {/* Left Column (60%) */}
                    <div className="lg:w-[60%] space-y-8">

                        {/* Image Gallery */}
                        <div className="space-y-4">
                            <div className="aspect-[16/10] bg-gray-100 rounded-lg overflow-hidden border">
                                <img src={service.images[0]} alt="Main" className="w-full h-full object-cover" />
                            </div>
                            <div className="grid grid-cols-4 gap-4">
                                {service.images.slice(1).map((img, idx) => (
                                    <div key={idx} className="aspect-[4/3] bg-gray-100 rounded-md overflow-hidden border cursor-pointer hover:opacity-80 transition-opacity">
                                        <img src={img} alt={`Thumb ${idx+1}`} className="w-full h-full object-cover" />
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Tabs */}
                        <Tabs defaultValue="overview" className="w-full">
                            <TabsList className="w-full justify-start border-b rounded-none h-auto p-0 bg-transparent mb-6">
                                <TabsTrigger value="overview" className="rounded-none border-b-2 border-transparent data-[state=active]:border-indigo-600 data-[state=active]:bg-transparent data-[state=active]:shadow-none py-3 px-6 text-base">
                                    Overview
                                </TabsTrigger>
                                <TabsTrigger value="reviews" className="rounded-none border-b-2 border-transparent data-[state=active]:border-indigo-600 data-[state=active]:bg-transparent data-[state=active]:shadow-none py-3 px-6 text-base">
                                    Reviews ({service.seller.reviews})
                                </TabsTrigger>
                            </TabsList>

                            <TabsContent value="overview" className="space-y-8">
                                {/* Description */}
                                <div>
                                    <h3 className="text-xl font-bold mb-4">About This Service</h3>
                                    <div
                                        className="prose max-w-none text-gray-700"
                                        dangerouslySetInnerHTML={{ __html: service.description }}
                                    />
                                </div>

                                {/* Seller Card */}
                                <div>
                                    <h3 className="text-xl font-bold mb-4">About The Seller</h3>
                                    <Card>
                                        <CardContent className="p-6">
                                            <div className="flex flex-col sm:flex-row items-center sm:items-start gap-6 text-center sm:text-left">
                                                <Avatar className="h-24 w-24">
                                                    <AvatarImage src={service.seller.avatar} alt={service.seller.name} />
                                                    <AvatarFallback>{service.seller.name.substring(0,2).toUpperCase()}</AvatarFallback>
                                                </Avatar>
                                                <div className="flex-1 space-y-2">
                                                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                                                        <div>
                                                            <h4 className="text-lg font-bold">{service.seller.name}</h4>
                                                            <p className="text-sm text-gray-500">{service.seller.handle}</p>
                                                        </div>
                                                        <Button variant="outline" className="mt-2 sm:mt-0">Contact Me</Button>
                                                    </div>

                                                    <div className="flex items-center justify-center sm:justify-start space-x-4 text-sm text-gray-600 pt-2 border-t">
                                                        <div className="flex items-center">
                                                            <Star className="h-4 w-4 text-amber-500 fill-current mr-1" />
                                                            <span className="font-semibold text-gray-900">{service.seller.rating}</span>
                                                            <span className="ml-1">({service.seller.reviews} reviews)</span>
                                                        </div>
                                                        <div className="hidden sm:block text-gray-300">|</div>
                                                        <div>Member since {service.seller.memberSince}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </CardContent>
                                    </Card>
                                </div>
                            </TabsContent>

                            <TabsContent value="reviews">
                                <p className="text-gray-500">Reviews content here...</p>
                            </TabsContent>
                        </Tabs>
                    </div>

                    {/* Right Column (40%) */}
                    <div className="lg:w-[40%]">
                        <div className="sticky top-8">
                            <Card className="border-gray-200 shadow-md">
                                <Tabs defaultValue="premium" className="w-full">
                                    <TabsList className="grid w-full grid-cols-3 rounded-t-xl rounded-b-none border-b h-14 bg-gray-50 p-0">
                                        <TabsTrigger value="basic" className="rounded-none border-r data-[state=active]:border-b-2 data-[state=active]:border-b-indigo-600 h-full data-[state=active]:shadow-none data-[state=active]:bg-white">Basic</TabsTrigger>
                                        <TabsTrigger value="standard" className="rounded-none border-r data-[state=active]:border-b-2 data-[state=active]:border-b-indigo-600 h-full data-[state=active]:shadow-none data-[state=active]:bg-white">Standard</TabsTrigger>
                                        <TabsTrigger value="premium" className="rounded-none data-[state=active]:border-b-2 data-[state=active]:border-b-indigo-600 h-full data-[state=active]:shadow-none data-[state=active]:bg-white">Premium</TabsTrigger>
                                    </TabsList>

                                    <div className="p-6">
                                        <TabsContent value="premium" className="mt-0 outline-none">
                                            <div className="flex justify-between items-start mb-4">
                                                <h3 className="text-xl font-bold">{selectedPackage.name}</h3>
                                                <span className="text-2xl font-bold text-gray-900">${selectedPackage.price}</span>
                                            </div>

                                            <p className="text-gray-600 text-sm mb-6 pb-6 border-b">
                                                The complete package for serious businesses needing a comprehensive brand identity.
                                            </p>

                                            <div className="flex items-center text-sm font-medium text-gray-700 mb-6 space-x-6">
                                                <div className="flex items-center">
                                                    <Clock className="h-4 w-4 mr-2" />
                                                    {selectedPackage.deliveryDays} Days Delivery
                                                </div>
                                                <div className="flex items-center">
                                                    <RefreshCw className="h-4 w-4 mr-2" />
                                                    {selectedPackage.revisions} Revisions
                                                </div>
                                            </div>

                                            <ul className="space-y-3 mb-8">
                                                {selectedPackage.features.map((feature, idx) => (
                                                    <li key={idx} className="flex items-start text-sm text-gray-600">
                                                        <Check className="h-5 w-5 text-green-500 mr-2 shrink-0" />
                                                        {feature}
                                                    </li>
                                                ))}
                                            </ul>

                                            <div className="bg-gray-50 rounded-lg p-4 mb-6 border flex justify-between items-center">
                                                <span className="text-sm font-medium text-gray-700">Your balance:</span>
                                                <span className={`text-sm font-bold ${hasInsufficientBalance ? 'text-red-600' : 'text-green-600'}`}>
                                                    ${userBalance.toFixed(2)}
                                                </span>
                                            </div>

                                            {hasInsufficientBalance ? (
                                                <div className="space-y-3">
                                                    <div className="text-sm text-red-600 font-medium text-center bg-red-50 p-2 rounded">
                                                        Need ${(selectedPackage.price - userBalance).toFixed(2)} more to purchase
                                                    </div>
                                                    <Button className="w-full bg-slate-900 hover:bg-slate-800" size="lg">
                                                        Top up wallet
                                                    </Button>
                                                </div>
                                            ) : (
                                                <Button className="w-full bg-indigo-600 hover:bg-indigo-700 text-white flex justify-between items-center px-6" size="lg">
                                                    <span>Continue</span>
                                                    <span>${selectedPackage.price} &rarr;</span>
                                                </Button>
                                            )}
                                        </TabsContent>
                                        <TabsContent value="basic" className="mt-0 outline-none">
                                            <p className="text-gray-500 text-center py-8">Basic package details...</p>
                                        </TabsContent>
                                        <TabsContent value="standard" className="mt-0 outline-none">
                                            <p className="text-gray-500 text-center py-8">Standard package details...</p>
                                        </TabsContent>
                                    </div>
                                </Tabs>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>
        </MarketplaceLayout>
    );
}

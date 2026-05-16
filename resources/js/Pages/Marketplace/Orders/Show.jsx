import React from 'react';
import { Head, Link } from '@inertiajs/react';
import MarketplaceLayout from '@/Layouts/MarketplaceLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Textarea } from '@/Components/ui/textarea';
import { ScrollArea } from '@/Components/ui/scroll-area';
import { ChevronLeft, CheckCircle2, Clock, Upload, Send, FileText, Check } from 'lucide-react';

export default function Show() {
    const order = {
        id: 'ORD-1029',
        status: 'delivered', // pending, in_progress, delivered, completed
        createdAt: 'Oct 15, 2023',
        deadline: 'Oct 18, 2023 (in 12 hours)',
        amount: 99.00,
        commission: 19.80,
        netAmount: 79.20,
        service: {
            title: 'Logo Design for Startups',
            image: 'https://placehold.co/100x75/png'
        },
        package: {
            name: 'Pro Package',
            features: ['5 Logo Concepts', 'High Res JPG/PNG', 'Vector Files', 'Social Media Kit', 'Unlimited Revisions']
        },
        seller: {
            name: 'Sara Design',
            handle: '@sara_design',
            avatar: '/avatars/02.png'
        },
        requirements: "I need a minimalist logo for my new fintech startup called 'Vault'. Primary color should be a deep blue. I want it to convey trust and security but still feel modern.",
        delivery: {
            note: "Hi there! I've completed the logo designs based on your requirements. I went with a very clean, geometric approach for the icon to represent a modern vault. Please find the source files and exports attached. Let me know if you need any revisions!",
            files: ['Vault_Logo_Concepts_v1.pdf', 'Vault_Assets.zip'],
            deliveredAt: '2 hours ago'
        }
    };

    const isBuyer = false; // Simulating viewed by seller to show price + commission breakdown as requested

    const steps = [
        { id: 'pending', label: 'Requirements Pending', isCompleted: true },
        { id: 'in_progress', label: 'In Progress', isCompleted: true },
        { id: 'delivered', label: 'Delivered', isCompleted: order.status === 'delivered' || order.status === 'completed', isActive: order.status === 'delivered' },
        { id: 'completed', label: 'Completed', isCompleted: order.status === 'completed' }
    ];

    return (
        <MarketplaceLayout>
            <Head title={`Order ${order.id}`} />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Back Link */}
                <Link href={route('marketplace.orders.index')} className="inline-flex items-center text-sm text-gray-500 hover:text-gray-900 mb-6">
                    <ChevronLeft className="h-4 w-4 mr-1" />
                    Back to Orders
                </Link>

                <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900 flex items-center gap-3">
                            Order <span className="font-mono text-indigo-600">{order.id}</span>
                        </h1>
                        <p className="text-gray-500 mt-1">Placed on {order.createdAt}</p>
                    </div>
                </div>

                {/* Progress Bar */}
                <div className="bg-white rounded-xl shadow-sm border p-6 mb-8">
                    <div className="relative">
                        <div className="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-gray-100 rounded-full"></div>
                        <div
                            className="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-indigo-600 rounded-full transition-all duration-500"
                            style={{ width: order.status === 'pending' ? '0%' : order.status === 'in_progress' ? '33%' : order.status === 'delivered' ? '66%' : '100%' }}
                        ></div>

                        <div className="relative flex justify-between">
                            {steps.map((step, idx) => (
                                <div key={step.id} className="flex flex-col items-center">
                                    <div className={`w-8 h-8 rounded-full flex items-center justify-center border-2 z-10 bg-white
                                        ${step.isCompleted ? 'border-indigo-600 text-indigo-600' : 'border-gray-200 text-gray-300'}
                                        ${step.isActive ? 'ring-4 ring-indigo-100 bg-indigo-50' : ''}
                                    `}>
                                        {step.isCompleted ? <CheckCircle2 className="h-5 w-5 fill-indigo-100 text-indigo-600" /> : <div className="w-2.5 h-2.5 rounded-full bg-current"></div>}
                                    </div>
                                    <span className={`mt-3 text-sm font-medium ${step.isCompleted || step.isActive ? 'text-gray-900' : 'text-gray-400'}`}>
                                        {step.label}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>

                <div className="flex flex-col lg:flex-row gap-8">
                    {/* Left Column (60%) */}
                    <div className="lg:w-[60%] space-y-6">

                        {/* Delivery Section (If delivered) */}
                        {order.status === 'delivered' && isBuyer && (
                            <Card className="border-green-200 shadow-sm bg-green-50/30">
                                <CardHeader className="bg-green-50/50 border-b border-green-100 pb-4">
                                    <div className="flex justify-between items-center">
                                        <CardTitle className="text-green-800 flex items-center">
                                            <Upload className="mr-2 h-5 w-5 text-green-600" />
                                            Order Delivered!
                                        </CardTitle>
                                        <span className="text-xs text-green-600 font-medium">{order.delivery.deliveredAt}</span>
                                    </div>
                                </CardHeader>
                                <CardContent className="p-6">
                                    <div className="bg-white p-4 rounded-lg border border-green-100 mb-6 text-gray-700 text-sm whitespace-pre-line">
                                        {order.delivery.note}
                                    </div>

                                    <h4 className="font-semibold text-gray-900 mb-3 text-sm uppercase tracking-wider">Attached Files</h4>
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-8">
                                        {order.delivery.files.map((file, idx) => (
                                            <div key={idx} className="flex items-center p-3 bg-white border rounded-md hover:border-indigo-300 transition-colors cursor-pointer group">
                                                <FileText className="h-8 w-8 text-indigo-100 fill-indigo-600 mr-3 group-hover:scale-110 transition-transform" />
                                                <div className="flex-1 min-w-0">
                                                    <p className="text-sm font-medium text-gray-900 truncate">{file}</p>
                                                    <p className="text-xs text-gray-500">2.4 MB</p>
                                                </div>
                                            </div>
                                        ))}
                                    </div>

                                    <div className="flex flex-col sm:flex-row gap-4 pt-4 border-t border-green-200">
                                        <Button className="flex-1 bg-green-600 hover:bg-green-700 text-white shadow-sm" size="lg">
                                            <Check className="mr-2 h-5 w-5" /> Accept & Complete Order
                                        </Button>
                                        <Button variant="outline" className="flex-1 border-gray-300 hover:bg-gray-50" size="lg">
                                            Request Revision
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {/* Order Details */}
                        <Card>
                            <CardHeader className="pb-4">
                                <CardTitle>Requirements</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="bg-gray-50 p-4 rounded-lg border text-gray-700 text-sm whitespace-pre-line">
                                    {order.requirements}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Embedded Chat */}
                        <Card className="flex flex-col h-[500px]">
                            <CardHeader className="border-b py-4">
                                <CardTitle className="text-base flex items-center">
                                    <Avatar className="h-8 w-8 mr-3">
                                        <AvatarImage src={order.seller.avatar} />
                                        <AvatarFallback>SD</AvatarFallback>
                                    </Avatar>
                                    <div>
                                        <div className="font-semibold text-gray-900">{order.seller.name}</div>
                                        <div className="text-xs text-gray-500">{order.seller.handle}</div>
                                    </div>
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="flex-1 p-0 flex flex-col">
                                <ScrollArea className="flex-1 p-4 bg-gray-50/50">
                                    <div className="space-y-4">
                                        <div className="flex justify-center">
                                            <span className="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded-full">Oct 15, 2023</span>
                                        </div>

                                        <div className="flex justify-start">
                                            <div className="bg-white border rounded-2xl rounded-tl-sm px-4 py-2.5 max-w-[80%] shadow-sm">
                                                <p className="text-sm text-gray-800">Hi! I've received your order and requirements. I'll get started right away. Let me know if you have any questions.</p>
                                                <span className="text-[10px] text-gray-400 mt-1 block">10:45 AM</span>
                                            </div>
                                        </div>

                                        <div className="flex justify-end">
                                            <div className="bg-indigo-600 text-white rounded-2xl rounded-tr-sm px-4 py-2.5 max-w-[80%] shadow-sm">
                                                <p className="text-sm">Great, thank you! Looking forward to seeing the concepts.</p>
                                                <span className="text-[10px] text-indigo-200 mt-1 block text-right">11:02 AM</span>
                                            </div>
                                        </div>
                                    </div>
                                </ScrollArea>
                                <div className="p-4 border-t bg-white">
                                    <div className="flex gap-2">
                                        <Textarea
                                            placeholder="Type a message..."
                                            className="min-h-[44px] h-[44px] resize-none focus-visible:ring-1"
                                        />
                                        <Button size="icon" className="h-[44px] w-[44px] shrink-0 bg-indigo-600 hover:bg-indigo-700">
                                            <Send className="h-5 w-5" />
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Right Column (40%) */}
                    <div className="lg:w-[40%]">
                        <div className="sticky top-8 space-y-6">

                            {/* Order Info Card */}
                            <Card>
                                <CardHeader className="pb-4">
                                    <CardTitle>Order Summary</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-6">

                                    <div className="flex items-center gap-4 pb-6 border-b">
                                        <img src={order.service.image} alt="Service" className="w-16 h-12 object-cover rounded-md border" />
                                        <div>
                                            <h4 className="font-medium text-gray-900 line-clamp-1">{order.service.title}</h4>
                                            <Badge variant="secondary" className="mt-1 font-normal">{order.package.name}</Badge>
                                        </div>
                                    </div>

                                    <div>
                                        <h4 className="text-sm font-semibold text-gray-900 mb-3">Included in this package:</h4>
                                        <ul className="space-y-2">
                                            {order.package.features.map((feature, idx) => (
                                                <li key={idx} className="flex items-start text-sm text-gray-600">
                                                    <Check className="h-4 w-4 text-gray-400 mr-2 shrink-0 mt-0.5" />
                                                    {feature}
                                                </li>
                                            ))}
                                        </ul>
                                    </div>

                                    <div className="bg-gray-50 p-4 rounded-lg border">
                                        {!isBuyer ? (
                                            <>
                                                <div className="flex justify-between items-center mb-2">
                                                    <span className="text-sm text-gray-500">Order Price</span>
                                                    <span className="font-medium text-gray-900">${order.amount.toFixed(2)}</span>
                                                </div>
                                                <div className="flex justify-between items-center mb-2">
                                                    <span className="text-sm text-gray-500">Commission (20%)</span>
                                                    <span className="text-sm text-red-500">-${order.commission.toFixed(2)}</span>
                                                </div>
                                                <div className="flex justify-between items-center mt-3 pt-3 border-t border-gray-200">
                                                    <span className="text-sm font-medium text-gray-900">Your Earnings</span>
                                                    <span className="font-bold text-lg text-green-600">${order.netAmount.toFixed(2)}</span>
                                                </div>
                                            </>
                                        ) : (
                                            <div className="flex justify-between items-center mb-2">
                                                <span className="text-sm text-gray-500">Order Total</span>
                                                <span className="font-bold text-lg text-gray-900">${order.amount.toFixed(2)}</span>
                                            </div>
                                        )}
                                        {order.status !== 'completed' && (
                                            <div className="flex items-center text-amber-600 text-sm font-medium mt-4 pt-4 border-t border-gray-200">
                                                <Clock className="h-4 w-4 mr-1.5" />
                                                Deadline: {order.deadline}
                                            </div>
                                        )}
                                    </div>

                                </CardContent>
                            </Card>

                            {/* Seller Actions (If viewed by seller) */}
                            {!isBuyer && order.status === 'delivered' && (
                                <Card className="border-indigo-200 shadow-md">
                                    <CardHeader className="bg-indigo-50 border-b border-indigo-100">
                                        <CardTitle className="text-indigo-800">Submit Delivery</CardTitle>
                                    </CardHeader>
                                    <CardContent className="p-6 space-y-4">
                                        <div className="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:bg-gray-50 transition-colors cursor-pointer">
                                            <Upload className="h-8 w-8 text-gray-400 mx-auto mb-3" />
                                            <p className="text-sm font-medium text-gray-900">Click to upload files</p>
                                            <p className="text-xs text-gray-500 mt-1">Maximum file size 1GB</p>
                                        </div>

                                        <div className="space-y-2">
                                            <label className="text-sm font-medium text-gray-900">Delivery Note</label>
                                            <Textarea placeholder="Describe what you are delivering..." className="min-h-[100px]" />
                                        </div>

                                        <Button className="w-full bg-indigo-600 hover:bg-indigo-700">
                                            Submit Delivery
                                        </Button>
                                    </CardContent>
                                </Card>
                            )}

                        </div>
                    </div>
                </div>
            </div>
        </MarketplaceLayout>
    );
}

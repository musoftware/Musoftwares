import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Textarea } from '@/Components/ui/textarea';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/Components/ui/dialog';
import { Check, X, Tag } from 'lucide-react';

export default function Pending({ auth }) {
    const [rejectDialogOpen, setRejectDialogOpen] = useState(false);
    const [selectedService, setSelectedService] = useState(null);

    const pendingServices = [
        {
            id: 1,
            title: 'Modern E-commerce Website with React & Node',
            seller: {
                name: 'Alex Developer',
                handle: '@alex_dev',
                avatar: '/avatars/03.png'
            },
            category: 'Development',
            tags: ['React', 'Node.js', 'E-commerce', 'Stripe'],
            description: 'I will build a fully functional, responsive e-commerce website using modern technologies. Includes payment gateway integration, admin panel, and user authentication.',
            image: 'https://placehold.co/600x400/png',
            submittedAt: '2 hours ago'
        },
        {
            id: 2,
            title: 'Voiceover for Commercials and YouTube',
            seller: {
                name: 'Sarah Voice',
                handle: '@sarah_v',
                avatar: '/avatars/04.png'
            },
            category: 'Audio',
            tags: ['Voiceover', 'Commercial', 'English', 'Professional'],
            description: 'Professional studio-quality voiceover for your next commercial, YouTube video, or explainer. Fast delivery and high-quality WAV files provided.',
            image: 'https://placehold.co/600x400/png',
            submittedAt: '5 hours ago'
        }
    ];

    const handleRejectClick = (service) => {
        setSelectedService(service);
        setRejectDialogOpen(true);
    };

    return (
        <AdminLayout user={auth?.user}>
            <Head title="Pending Services - Marketplace" />

            <div className="mb-8">
                <h1 className="text-2xl font-bold text-gray-900">Pending Services</h1>
                <p className="text-gray-500 mt-1">Review and approve services submitted to the marketplace.</p>
            </div>

            <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
                {pendingServices.map(service => (
                    <Card key={service.id} className="overflow-hidden">
                        <div className="flex flex-col sm:flex-row h-full">
                            {/* Image Section */}
                            <div className="sm:w-2/5 aspect-video sm:aspect-auto relative bg-gray-100 border-r">
                                <img src={service.image} alt={service.title} className="w-full h-full object-cover" />
                                <Badge className="absolute top-3 left-3 bg-white text-gray-800 border shadow-sm">
                                    {service.category}
                                </Badge>
                            </div>

                            {/* Content Section */}
                            <div className="flex-1 flex flex-col p-6">
                                <div className="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 className="text-lg font-bold text-gray-900 leading-tight mb-2">
                                            {service.title}
                                        </h3>
                                        <div className="flex items-center">
                                            <Avatar className="h-6 w-6 mr-2">
                                                <AvatarImage src={service.seller.avatar} />
                                                <AvatarFallback>{service.seller.name.substring(0,2)}</AvatarFallback>
                                            </Avatar>
                                            <span className="text-sm font-medium text-gray-900">{service.seller.name}</span>
                                            <span className="text-sm text-gray-500 ml-1">({service.seller.handle})</span>
                                        </div>
                                    </div>
                                    <span className="text-xs text-gray-400 whitespace-nowrap ml-4">{service.submittedAt}</span>
                                </div>

                                <p className="text-sm text-gray-600 mb-4 line-clamp-3">
                                    {service.description}
                                </p>

                                <div className="flex flex-wrap gap-1.5 mb-6">
                                    <Tag className="h-4 w-4 text-gray-400 mr-1 mt-0.5" />
                                    {service.tags.map(tag => (
                                        <Badge key={tag} variant="secondary" className="text-xs font-normal bg-gray-100 text-gray-600">
                                            {tag}
                                        </Badge>
                                    ))}
                                </div>

                                <div className="mt-auto pt-4 border-t flex justify-end gap-3">
                                    <Button variant="outline" className="border-red-200 text-red-600 hover:bg-red-50 hover:text-red-700" onClick={() => handleRejectClick(service)}>
                                        <X className="mr-2 h-4 w-4" />
                                        Reject
                                    </Button>
                                    <Button className="bg-green-600 hover:bg-green-700 text-white shadow-sm">
                                        <Check className="mr-2 h-4 w-4" />
                                        Approve Service
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </Card>
                ))}

                {pendingServices.length === 0 && (
                    <div className="col-span-full py-12 text-center text-gray-500 border-2 border-dashed rounded-lg bg-gray-50">
                        <Check className="h-12 w-12 text-gray-300 mx-auto mb-3" />
                        <h3 className="text-lg font-medium text-gray-900 mb-1">All caught up!</h3>
                        <p>There are no pending services awaiting approval.</p>
                    </div>
                )}
            </div>

            {/* Rejection Dialog */}
            <Dialog open={rejectDialogOpen} onOpenChange={setRejectDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="text-red-600">Reject Service</DialogTitle>
                        <DialogDescription>
                            Provide a reason for rejecting <span className="font-semibold text-gray-900">"{selectedService?.title}"</span>. This note will be sent to the seller.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="my-4">
                        <Textarea
                            placeholder="Example: The provided images are low resolution, or the description lacks detail..."
                            className="min-h-[120px] focus-visible:ring-red-500"
                        />
                    </div>

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setRejectDialogOpen(false)}>Cancel</Button>
                        <Button variant="destructive">Confirm Rejection</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

        </AdminLayout>
    );
}

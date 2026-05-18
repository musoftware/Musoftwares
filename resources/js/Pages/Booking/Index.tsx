import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { AppPage } from '@/Components/ui/AppPage';
import { PageHeader } from '@/Components/ui/PageHeader';
import { SectionCard } from '@/Components/ui/SectionCard';
import { EmptyState } from '@/Components/ui/EmptyState';
import { Button } from '@/Components/ui/button';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Calendar, Clock, Copy, Plus, MoreHorizontal } from 'lucide-react';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';

interface EventType {
    id: number;
    title: string;
    slug: string;
    description: string;
    duration_minutes: number;
    price: number;
    currency: string;
    requires_payment: boolean;
    is_active: boolean;
}

export default function Index({ events }: { events: EventType[] }) {
    const copyLink = (slug: string) => {
        navigator.clipboard.writeText(`${window.location.origin}/book/${slug}`);
        // Optionally add a toast here
    };

    return (
        <AppPage>
            <Head title="Booking Events" />
            
            <PageHeader
                title="Event Types"
                description="Create and manage your booking event types."
                actions={
                    <Button asChild>
                        <Link href={route('booking.events.create')}>
                            <Plus className="w-4 h-4 mr-2" />
                            New Event Type
                        </Link>
                    </Button>
                }
            />

            <div className="mt-6">
                {events.length === 0 ? (
                    <EmptyState
                        icon={Calendar}
                        title="No event types yet"
                        description="Create an event type to start accepting bookings."
                        action={{
                            label: "Create Event Type",
                            href: route('booking.events.create')
                        }}
                    />
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {events.map((event) => (
                            <Card key={event.id} className="relative group transition-all hover:shadow-md">
                                <CardHeader className="pb-3">
                                    <div className="flex justify-between items-start">
                                        <CardTitle className="text-lg">{event.title}</CardTitle>
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" className="h-8 w-8 p-0">
                                                    <MoreHorizontal className="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem asChild>
                                                    <Link href={route('booking.events.edit', event.slug)}>
                                                        Edit
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem onClick={() => copyLink(event.slug)}>
                                                    Copy Link
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </div>
                                    <CardDescription className="line-clamp-2 mt-1 min-h-[40px]">
                                        {event.description || "No description provided."}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="flex flex-wrap gap-2 text-sm text-muted-foreground">
                                        <div className="flex items-center">
                                            <Clock className="w-4 h-4 mr-1.5" />
                                            {event.duration_minutes} min
                                        </div>
                                        {event.requires_payment && event.price && (
                                            <div className="flex items-center">
                                                <CurrencyDisplay amount={event.price} currency={event.currency || 'USD'} className="text-muted-foreground font-normal" />
                                            </div>
                                        )}
                                    </div>
                                </CardContent>
                                <CardFooter className="pt-0 border-t mt-4 border-slate-100 flex justify-between items-center bg-slate-50/50 p-4 rounded-b-xl">
                                    <Badge variant={event.is_active ? "default" : "secondary"} className="font-normal">
                                        {event.is_active ? 'Active' : 'Draft'}
                                    </Badge>
                                    
                                    <Button variant="ghost" size="sm" onClick={() => copyLink(event.slug)} className="text-slate-500 hover:text-slate-900">
                                        <Copy className="w-4 h-4 mr-2" />
                                        Copy link
                                    </Button>
                                </CardFooter>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </AppPage>
    );
}

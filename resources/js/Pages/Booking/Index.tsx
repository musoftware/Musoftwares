import React from 'react';
import { Head, Link } from '@inertiajs/react';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { EmptyState } from '@/Components/ui/EmptyState';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Calendar, Clock, Copy, Plus, MoreHorizontal, Users, CalendarOff } from 'lucide-react';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { CurrencyDisplay } from '@/Components/ui/CurrencyDisplay';
import { __ } from '@/lib/i18n';

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
        <WorkspaceLayout
            title={__('general.booking_events')}
            workspaceName="Booking Settings"
            tenantId="SYS-BOOKING"
            menuItems={[
                { id: 'dashboard', label: 'Dashboard', icon: Calendar, href: '/booking', isActive: false },
                { id: 'appointments', label: 'Appointments', icon: Clock, href: '/booking/appointments', isActive: false },
                { id: 'events', label: 'Event Types', icon: Calendar, href: '/booking/events', isActive: true },
                { id: 'providers', label: 'Providers', icon: Users, href: '/booking/providers', isActive: false },
                { id: 'exceptions', label: 'Exceptions', icon: CalendarOff, href: '/booking/exceptions', isActive: false },
            ]}
        >
            <Head title={__('general.booking_events')} />
            
            <div className="space-y-8">
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">{__('general.event_types')}</h1>
                        <p className="text-muted-foreground">{__('general.create_and_manage_your_booking_event_types')}</p>
                    </div>
                    <div className="mt-4 sm:mt-0">
                        <Link 
                            href={route('booking.events.create')} 
                            className="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 transition-colors shadow-sm"
                        >
                            <Plus className="w-4 h-4 me-2" />{__('general.new_event_type')}</Link>
                    </div>
                </div>

            <div className="mt-6">
                {events.length === 0 ? (
                    <EmptyState
                        icon={Calendar}
                        title={__('general.no_event_types_yet')}
                        description={__('general.create_an_event_type_to_start_accepting_bookings')}
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
                                            <DropdownMenuTrigger className="touch-target inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 hover:bg-slate-100 hover:text-slate-900 h-8 w-8 p-0">
                                                <MoreHorizontal className="h-4 w-4 text-slate-500" />
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem className="cursor-pointer" onClick={() => window.location.href = route('booking.events.edit', event.slug)}>
                                                    {__('general.edit')}</DropdownMenuItem>
                                                <DropdownMenuItem className="cursor-pointer" onClick={() => copyLink(event.slug)}>{__('general.copy_link')}</DropdownMenuItem>
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
                                            <Clock className="w-4 h-4 me-1.5" />
                                            {event.duration_minutes} min
                                        </div>
                                        {event.requires_payment && event.price && (
                                            <div className="flex items-center">
                                                <CurrencyDisplay amount={event.price} currency={event.currency} className="text-muted-foreground font-normal" />
                                            </div>
                                        )}
                                    </div>
                                </CardContent>
                                <CardFooter className="pt-0 border-t mt-4 border-slate-100 flex justify-between items-center bg-slate-50/50 p-4 rounded-b-xl">
                                    <Badge variant={event.is_active ? "default" : "secondary"} className="font-normal">
                                        {event.is_active ? 'Active' : 'Draft'}
                                    </Badge>
                                    
                                    <button onClick={() => copyLink(event.slug)} className="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 hover:bg-slate-100 h-8 px-3 text-slate-500 hover:text-slate-900">
                                        <Copy className="w-4 h-4 me-2" />{__('general.copy_link_1')}</button>
                                </CardFooter>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
            </div>
        </WorkspaceLayout>
    );
}

